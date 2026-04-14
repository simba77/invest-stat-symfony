<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

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
}
