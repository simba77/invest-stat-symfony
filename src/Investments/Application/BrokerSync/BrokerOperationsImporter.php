<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationRepositoryInterface;
use App\Investments\Domain\BrokerSync\Client\BrokerClientFactoryInterface;
use App\Investments\Domain\BrokerSync\Client\ExternalOperation;
use App\Investments\Domain\BrokerSync\TokenCipherInterface;

/**
 * Copies new and revised broker operations into the journal.
 */
final readonly class BrokerOperationsImporter
{
    /** Brokers revise recent operations (states, late fees), so every sync re-reads a few days. */
    private const string OVERLAP = '-7 days';

    /** For accounts whose broker does not say when they were opened. */
    private const string EARLIEST_HISTORY = '2015-01-01';

    private const int BATCH_SIZE = 500;

    public function __construct(
        private BrokerClientFactoryInterface $clientFactory,
        private TokenCipherInterface $tokenCipher,
        private BrokerOperationRepositoryInterface $operationRepository,
    ) {
    }

    /**
     * @return int the number of operations that were not in the journal yet
     */
    public function import(BrokerAccountLink $link, \DateTimeImmutable $until): int
    {
        $operations = $this->clientFactory->forProvider($link->getProvider())->getOperations(
            $this->tokenCipher->decrypt($link->getEncryptedToken()),
            $link->getExternalAccountId(),
            $this->importFrom($link),
            $until,
        );

        $new = 0;
        $batch = [];
        foreach ($operations as $operation) {
            $batch[$operation->id] = $operation;
            if (count($batch) >= self::BATCH_SIZE) {
                $new += $this->store($link, $batch);
                $batch = [];
            }
        }

        return $new + $this->store($link, $batch);
    }

    private function importFrom(BrokerAccountLink $link): \DateTimeImmutable
    {
        return $link->getLastSyncedAt()?->modify(self::OVERLAP)
            ?? $link->getExternalAccountOpenedAt()
            ?? new \DateTimeImmutable(self::EARLIEST_HISTORY);
    }

    /**
     * @param array<string, ExternalOperation> $batch
     */
    private function store(BrokerAccountLink $link, array $batch): int
    {
        $known = $this->operationRepository->findByExternalIds($link, array_map('strval', array_keys($batch)));

        $new = 0;
        $operations = [];
        foreach ($batch as $id => $external) {
            $operation = $known[$id] ?? null;
            if ($operation === null) {
                $operation = new BrokerOperation($link, $external);
                $new++;
            } else {
                $operation->update($external);
            }
            $operations[] = $operation;
        }

        $this->operationRepository->saveAll($operations);

        return $new;
    }
}
