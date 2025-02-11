<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Shared\Domain\User;

interface AccountRepositoryInterface
{
    /**
     * @return array<int, array{account: Account, deposits_sum: string | null}>
     */
    public function findByUserWithDeposits(User $user): array;

    /**
     * @return array{account: Account, deposits_sum: string | null} | null
     */
    public function findByIdAndUserWithDeposits(int $id, User $user): ?array;

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

    public function findById(int $id): ?Account;
}
