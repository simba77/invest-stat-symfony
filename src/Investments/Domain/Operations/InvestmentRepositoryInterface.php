<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

interface InvestmentRepositoryInterface
{
    /**
     * @return array<int, array{investment: Investment, account_name: string}>
     */
    public function getByUserId(int $userId): array;

    public function getSumByUserId(int $userId): string;
}
