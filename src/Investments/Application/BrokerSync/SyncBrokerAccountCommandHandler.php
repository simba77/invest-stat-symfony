<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerSyncDisabledException;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Brings a linked account up to date with its broker.
 */
#[AsMessageHandler]
final readonly class SyncBrokerAccountCommandHandler
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private BrokerAccountLinkRepositoryInterface $linkRepository,
        private BrokerOperationsImporter $operationsImporter,
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

        $syncedAt = $this->clock->now();
        try {
            $this->operationsImporter->import($link, $syncedAt);
        } catch (\Throwable $exception) {
            $this->recordFailure($command->accountId, $exception);
            throw $exception;
        }

        $link->markSynced($syncedAt, []);
        $this->linkRepository->save($link);
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
