<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Domain\Accounts\Account;
interface InvestmentRepositoryInterface
{
    /**
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    public function getByUserId(int $userId): array;

    /**
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    public function getPageByUserId(int $userId, int $offset, int $limit): array;

    public function countByUserId(int $userId): int;

    public function getSumByUserId(int $userId): string;

    public function getGrossSumByUserId(int $userId): string;

    /**
     * @return list<array{date: string, sum: string}>
     */
    public function getDailyCashFlowsByUserId(int $userId): array;

    /**
     * What was put into the account less what was taken out, in roubles.
     *
     * @return numeric-string
     */
    public function sumByAccount(Account $account): string;

    /**
     * Every record of the account, in the order they were created.
     *
     * @return list<Investment>
     */
    public function findByAccount(Account $account): array;
}
