<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\History;

/**
 * The operations of all the accounts of a user, the latest first.
 */
interface OperationHistoryRepositoryInterface
{
    public function count(int $userId, OperationHistoryFilter $filter): int;

    /**
     * @return list<OperationHistoryEntry>
     */
    public function findPage(int $userId, OperationHistoryFilter $filter, int $offset, int $limit): array;
}
