<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

interface BrokerOperationRepositoryInterface
{
    /**
     * @param list<string> $externalIds
     * @return array<string, BrokerOperation> indexed by external id
     */
    public function findByExternalIds(BrokerAccountLink $link, array $externalIds): array;

    /**
     * Executed operations in the order they happened.
     *
     * @return list<BrokerOperation>
     */
    public function findExecuted(BrokerAccountLink $link): array;

    public function countByLink(BrokerAccountLink $link): int;

    /**
     * @param list<BrokerOperation> $operations
     */
    public function saveAll(array $operations): void;

    public function removeByLink(BrokerAccountLink $link): void;
}
