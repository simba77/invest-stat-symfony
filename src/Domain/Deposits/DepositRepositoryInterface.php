<?php

declare(strict_types=1);

namespace App\Domain\Deposits;

use App\Domain\Shared\User;

interface DepositRepositoryInterface
{
    public function getSumOfDepositsForUser(User $user): string;

    /** @return array<Deposit> */
    public function getDepositsForUser(User $user): array;

    public function getDepositByIdAndUser(int $id, User $user): ?Deposit;
}
