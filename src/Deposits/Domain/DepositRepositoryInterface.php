<?php

declare(strict_types=1);

namespace App\Deposits\Domain;

interface DepositRepositoryInterface
{
    public function getSumOfDepositsForUserId(int $userId): string;

    /** @return array<Deposit> */
    public function getDepositsForUserId(int $userId): array;

    /** @return array<Deposit> */
    public function getDepositsPageForUserId(int $userId, int $offset, int $limit): array;

    public function countByUserId(int $userId): int;

    public function getDepositByIdAndUserId(int $id, int $userId): ?Deposit;

    public function save(Deposit $deposit): void;

    public function remove(Deposit $deposit): void;

    /** @return array<array{month: string, deposits: string, profit: string}> */
    public function getMonthlyStats(int $userId): array;

    /**
     * Returns all transactions grouped by account_id, ordered by date and id asc.
     * Used to calculate active days (periods when balance > 0).
     *
     * @return array<int, array<array{sum: string, date: string}>>
     */
    public function getTransactionsByAccount(int $userId): array;
}
