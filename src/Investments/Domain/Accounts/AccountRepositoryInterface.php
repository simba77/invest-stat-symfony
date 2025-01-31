<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Shared\Domain\User;

interface AccountRepositoryInterface
{
    /**
     * @param User $user
     * @return array<int, array{account: Account, deposits_sum: string | null}>
     */
    public function findByUserWithDeposits(User $user): array;

    public function getByIdAndUser(int $id, User $user): ?Account;

    /**
     * @return Account[]
     */
    public function findByUser(User $user): array;

    /**
     * @return Account[]
     */
    public function findAll(): array;

    public function save(Account $account): void;

    public function remove(Account $account): void;
}
