<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Broker\TInvest;

use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use App\Investments\Domain\BrokerSync\Client\ExternalInstrument;
use App\Investments\Domain\BrokerSync\Client\ExternalPosition;
use App\Investments\Domain\BrokerSync\Client\ExternalPositions;
use App\Investments\Domain\BrokerSync\InstrumentKind;
use Google\Protobuf\Timestamp;
use Metaseller\TinkoffInvestApi2\TinkoffClientsFactory;
use Tinkoff\Invest\V1\AccessLevel;
use Tinkoff\Invest\V1\BondResponse;
use Tinkoff\Invest\V1\GetAccountsRequest;
use Tinkoff\Invest\V1\GetAccountsResponse;
use Tinkoff\Invest\V1\GetOperationsByCursorRequest;
use Tinkoff\Invest\V1\GetOperationsByCursorResponse;
use Tinkoff\Invest\V1\InstrumentIdType;
use Tinkoff\Invest\V1\InstrumentRequest;
use Tinkoff\Invest\V1\InstrumentResponse;
use Tinkoff\Invest\V1\PositionsRequest;
use Tinkoff\Invest\V1\PositionsResponse;

/**
 * T-Bank Invest API, used with the token of the synced account.
 */
final class TInvestBrokerClient implements BrokerClientInterface
{
    private const int STATUS_OK = 0;
    private const int STATUS_NOT_FOUND = 5;
    private const int STATUS_UNAUTHENTICATED = 16;
    private const int OPERATIONS_PAGE_SIZE = 1000;

    /** @var array<string, TinkoffClientsFactory> */
    private array $clients = [];

    public function __construct(
        private readonly TInvestOperationMapper $operationMapper,
    ) {
    }

    #[\Override]
    public function getAccounts(string $token): array
    {
        /** @var GetAccountsResponse $response */
        $response = $this->call($this->client($token)->usersServiceClient->GetAccounts(new GetAccountsRequest())->wait());

        $accounts = [];
        foreach ($response->getAccounts() as $account) {
            $openedAt = $account->getOpenedDate();
            $accounts[] = new ExternalAccount(
                id:       $account->getId(),
                name:     $account->getName(),
                openedAt: $openedAt !== null && $openedAt->getSeconds() > 0 ? TInvestValues::dateTime($openedAt) : null,
                readOnly: $account->getAccessLevel() === AccessLevel::ACCOUNT_ACCESS_LEVEL_READ_ONLY,
            );
        }

        return $accounts;
    }

    #[\Override]
    public function getOperations(string $token, string $accountId, \DateTimeImmutable $from, \DateTimeImmutable $to): iterable
    {
        $cursor = '';
        do {
            $request = new GetOperationsByCursorRequest();
            $request->setAccountId($accountId);
            $request->setFrom($this->timestamp($from));
            $request->setTo($this->timestamp($to));
            $request->setLimit(self::OPERATIONS_PAGE_SIZE);
            $request->setCursor($cursor);

            /** @var GetOperationsByCursorResponse $response */
            $response = $this->call($this->client($token)->operationsServiceClient->GetOperationsByCursor($request)->wait());
            foreach ($response->getItems() as $item) {
                yield $this->operationMapper->map($item);
            }

            $cursor = $response->getHasNext() ? $response->getNextCursor() : '';
        } while ($cursor !== '');
    }

    #[\Override]
    public function getPositions(string $token, string $accountId): ExternalPositions
    {
        $request = new PositionsRequest();
        $request->setAccountId($accountId);
        /** @var PositionsResponse $response */
        $response = $this->call($this->client($token)->operationsServiceClient->GetPositions($request)->wait());

        $money = [];
        foreach ([$response->getMoney(), $response->getBlocked()] as $values) {
            foreach ($values as $value) {
                $currency = strtoupper($value->getCurrency());
                $money[$currency] = bcadd($money[$currency] ?? '0', TInvestValues::decimal($value->getUnits(), $value->getNano()), 9);
            }
        }

        $securities = [];
        foreach ($response->getSecurities() as $security) {
            $securities[] = new ExternalPosition(
                instrumentUid: $security->getInstrumentUid(),
                ticker:        $security->getTicker(),
                kind:          $this->instrumentKind($security->getInstrumentType()),
                quantity:      (int) $security->getBalance() + (int) $security->getBlocked(),
            );
        }

        return new ExternalPositions($money, $securities);
    }

    #[\Override]
    public function findInstrument(string $token, string $instrumentUid): ?ExternalInstrument
    {
        $request = new InstrumentRequest();
        $request->setIdType(InstrumentIdType::INSTRUMENT_ID_TYPE_UID);
        $request->setId($instrumentUid);

        [$response, $status] = $this->client($token)->instrumentsServiceClient->GetInstrumentBy($request)->wait();
        if ($status->code === self::STATUS_NOT_FOUND) {
            return null;
        }
        /** @var InstrumentResponse $response */
        $response = $this->call([$response, $status]);
        $instrument = $response->getInstrument();
        if ($instrument === null) {
            return null;
        }

        $kind = $this->instrumentKind($instrument->getInstrumentType());
        $nominal = null;
        if ($kind === InstrumentKind::Bond) {
            /** @var BondResponse $bondResponse */
            $bondResponse = $this->call($this->client($token)->instrumentsServiceClient->BondBy($request)->wait());
            $value = $bondResponse->getInstrument()?->getNominal();
            $nominal = $value !== null ? TInvestValues::decimal($value->getUnits(), $value->getNano()) : null;
        }

        return new ExternalInstrument(
            uid:       $instrument->getUid(),
            ticker:    $instrument->getTicker(),
            classCode: $instrument->getClassCode(),
            name:      $instrument->getName(),
            kind:      $kind,
            currency:  strtoupper($instrument->getCurrency()),
            lotSize:   $instrument->getLot(),
            isin:      $instrument->getIsin() !== '' ? $instrument->getIsin() : null,
            nominal:   $nominal,
        );
    }

    private function instrumentKind(string $type): ?InstrumentKind
    {
        return match ($type) {
            '' => null,
            'share' => InstrumentKind::Share,
            'etf' => InstrumentKind::Etf,
            'bond' => InstrumentKind::Bond,
            'futures' => InstrumentKind::Future,
            'currency' => InstrumentKind::Currency,
            default => InstrumentKind::Other,
        };
    }

    private function timestamp(\DateTimeImmutable $dateTime): Timestamp
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds($dateTime->getTimestamp());

        return $timestamp;
    }

    private function client(string $token): TinkoffClientsFactory
    {
        return $this->clients[hash('sha256', $token)] ??= TinkoffClientsFactory::create($token);
    }

    /**
     * Unwraps the `[response, status]` pair returned by a unary gRPC call.
     *
     * @param array{0: mixed, 1: object{code: int, details: string}} $result
     */
    private function call(array $result): object
    {
        [$response, $status] = $result;

        if ($status->code === self::STATUS_UNAUTHENTICATED) {
            throw new BrokerApiException('T-Bank rejected the token');
        }
        if ($status->code !== self::STATUS_OK || ! is_object($response)) {
            throw new BrokerApiException(sprintf('T-Bank API error %d: %s', $status->code, $status->details));
        }

        return $response;
    }
}
