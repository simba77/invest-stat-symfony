<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerOperationRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerSyncDisabledException;
use App\Investments\Domain\BrokerSync\Client\BrokerClientFactoryInterface;
use App\Investments\Domain\BrokerSync\Client\BrokerClientInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalPositions;
use App\Investments\Domain\BrokerSync\Ledger\LedgerReplayer;
use App\Investments\Domain\BrokerSync\Ledger\PositionsReconciler;
use App\Investments\Domain\BrokerSync\TokenCipherInterface;
use App\Investments\Domain\Instruments\ShareSplitRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Brings a linked account up to date with its broker: imports new operations, rebuilds the
 * account from the whole journal, takes cash balances from the broker and checks positions.
 */
#[AsMessageHandler]
final readonly class SyncBrokerAccountCommandHandler
{
    /** How many replay warnings the sync status keeps. */
    private const int MAX_WARNINGS = 20;

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private BrokerAccountLinkRepositoryInterface $linkRepository,
        private BrokerOperationRepositoryInterface $operationRepository,
        private ShareSplitRepositoryInterface $shareSplitRepository,
        private BrokerOperationsImporter $operationsImporter,
        private BrokerClientFactoryInterface $clientFactory,
        private TokenCipherInterface $tokenCipher,
        private LedgerReplayer $ledgerReplayer,
        private LedgerProjector $ledgerProjector,
        private PositionsReconciler $positionsReconciler,
        private AccountBalanceCalculator $accountBalanceCalculator,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncBrokerAccountCommand $command): void
    {
        $link = $this->link($command->accountId);
        if (! $link->isEnabled()) {
            throw BrokerSyncDisabledException::forAccount($command->accountId);
        }

        try {
            $this->sync($link);
        } catch (\Throwable $exception) {
            $this->recordFailure($command->accountId, $exception);
            throw $exception;
        }
    }

    private function sync(BrokerAccountLink $link): void
    {
        $syncedAt = $this->clock->now();
        $client = $this->clientFactory->forProvider($link->getProvider());
        $token = $this->tokenCipher->decrypt($link->getEncryptedToken());

        $this->operationsImporter->import($link, $syncedAt);
        $positions = $client->getPositions($token, $link->getExternalAccountId());

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->rebuild($link, $client, $token, $positions, $syncedAt);
            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    private function rebuild(
        BrokerAccountLink $link,
        BrokerClientInterface $client,
        string $token,
        ExternalPositions $positions,
        \DateTimeImmutable $syncedAt,
    ): void {
        $account = $link->getAccount();
        $ledger = $this->ledgerReplayer->replay(
            $this->operationRepository->findExecuted($link),
            $this->shareSplitRepository->findAll(),
            $link->getFeeAllocation(),
            $syncedAt,
        );

        $warnings = [
            ...$ledger->warnings,
            ...$this->ledgerProjector->project($account, $ledger, static fn (string $uid) => $client->findInstrument($token, $uid)),
            ...$this->updateCash($account, $positions),
        ];

        $this->accountBalanceCalculator->recalculateBalance($account);

        $warnings = array_values(array_unique($warnings));
        $link->markSynced(
            $syncedAt,
            $this->positionsReconciler->compare($ledger, $positions),
            $warnings !== [] ? implode("\n", array_slice($warnings, 0, self::MAX_WARNINGS)) : null,
        );
        $this->linkRepository->save($link);
    }

    /**
     * The broker knows the cash exactly; it is not derived from deals for a synced account.
     *
     * @return list<string> warnings
     */
    private function updateCash(Account $account, ExternalPositions $positions): array
    {
        $account->setBalance(bcadd($positions->money['RUB'] ?? '0', '0', 4));
        $account->setUsdBalance(bcadd($positions->money['USD'] ?? '0', '0', 4));

        $warnings = [];
        foreach ($positions->money as $currency => $amount) {
            if (! in_array($currency, ['RUB', 'USD'], true) && bccomp($amount, '0', 9) !== 0) {
                $warnings[] = sprintf('%s %s of cash is not shown: accounts keep roubles and dollars only', $amount, $currency);
            }
        }

        return $warnings;
    }

    private function link(int $accountId): BrokerAccountLink
    {
        $account = $this->accountRepository->findById($accountId);
        $link = $account !== null ? $this->linkRepository->findByAccount($account) : null;

        return $link ?? throw new NotFoundException(sprintf('Account with id "%s" is not linked to a broker', $accountId));
    }

    private function recordFailure(int $accountId, \Throwable $exception): void
    {
        $this->logger->error('Broker sync of account {account} failed: {message}', [
            'account'   => $accountId,
            'message'   => $exception->getMessage(),
            'exception' => $exception,
        ]);

        try {
            // Forget whatever the failed run changed in memory before saving the status
            $this->entityManager->clear();
            $link = $this->link($accountId);
            $link->markFailed($exception->getMessage());
            $this->linkRepository->save($link);
        } catch (\Throwable $recordingFailure) {
            $this->logger->error('Cannot record the failed broker sync: {message}', [
                'message'   => $recordingFailure->getMessage(),
                'exception' => $recordingFailure,
            ]);
        }
    }
}
