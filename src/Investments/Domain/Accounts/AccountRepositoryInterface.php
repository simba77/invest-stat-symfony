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
    public function findByUserIdWithDeposits(User $user): array;

    public function getByIdAndUser(int $id, User $user): ?Account;

    public function save(Account $account): void;

    public function remove(Account $account): void;
}
