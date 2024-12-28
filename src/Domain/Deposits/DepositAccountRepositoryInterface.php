<?php

declare(strict_types=1);

namespace App\Domain\Deposits;

use App\Domain\Shared\User;

interface DepositAccountRepositoryInterface
{
    /** @return array<DepositAccount> */
    public function getForUser(User $user): array;

    /**
     * @param User $user
     * @return list<array{id: int, name: string, balance: string, profit: string}>
     */
    public function getWithSummary(User $user): array;

    public function getByIdAndUser(int $id, User $user): ?DepositAccount;

    public function save(DepositAccount $depositAccount): void;

    public function remove(DepositAccount $depositAccount): void;
}
