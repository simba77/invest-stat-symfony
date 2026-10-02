<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Broker\TInvest;

use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalAccount;
use Google\Protobuf\Timestamp;
use Metaseller\TinkoffInvestApi2\TinkoffClientsFactory;
use Tinkoff\Invest\V1\AccessLevel;
use Tinkoff\Invest\V1\GetAccountsRequest;
use Tinkoff\Invest\V1\GetAccountsResponse;
use Tinkoff\Invest\V1\GetOperationsByCursorRequest;
use Tinkoff\Invest\V1\GetOperationsByCursorResponse;

/**
 * T-Bank Invest API, used with the token of the synced account.
 */
final class TInvestBrokerClient implements BrokerClientInterface
{
    private const int STATUS_OK = 0;
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
