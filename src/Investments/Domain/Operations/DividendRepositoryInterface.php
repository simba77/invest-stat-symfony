<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Domain\Accounts\Account;
use App\Shared\Domain\User;

interface DividendRepositoryInterface
{
    /**
     * @return array<Dividend>
     */
    public function findAll(): array;

    /**
     * @return array<Dividend>
     */
    public function findByUser(?User $user): array;

    /**
     * @return array<Dividend>
     */
    public function getPageByUserId(int $userId, int $offset, int $limit): array;

    public function countByUserId(int $userId): int;

    public function sumByTickerAndUserAndStockMarket(int $userId, string $ticker, string $stockMarket): string;

    /**
     * @return array<Dividend>
     */
    public function findByUserAndTickerAndStockMarket(int $userId, string $ticker, string $stockMarket): array;

    /**
     * What the dividends brought to the account, in the currency of their shares.
     *
     * @return array<string, numeric-string> by currency
     */
    public function sumByAccountAndCurrency(Account $account): array;

    /**
     * Every record of the account, in the order they were created.
     *
     * @return list<Dividend>
     */
    public function findByAccount(Account $account): array;

    /**
     * The record when it belongs to an account of the user.
     */
    public function findByIdAndUser(int $id, User $user): ?Dividend;
}
