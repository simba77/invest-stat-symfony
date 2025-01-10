<?php

declare(strict_types=1);

namespace App\Deposits\Domain;

use App\Domain\Shared\User;

interface DepositRepositoryInterface
{
    public function getSumOfDepositsForUser(User $user): string;

    /** @return array<Deposit> */
    public function getDepositsForUser(User $user): array;

    public function getDepositByIdAndUser(int $id, User $user): ?Deposit;

    public function save(Deposit $deposit): void;

    public function remove(Deposit $deposit): void;
}
