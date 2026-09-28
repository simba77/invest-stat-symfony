<?php

declare(strict_types=1);

namespace App\Tests\Deposits;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\DepositAccount;
use App\Shared\Domain\User;

/**
 * @psalm-require-extends \App\Tests\Support\ApiTestCase
 */
trait CreatesDeposits
{
    private const int DEPOSIT = 1;
    private const int PERCENT = 2;

    private function createAccount(User $owner, string $name = 'Bank'): DepositAccount
    {
        $account = new DepositAccount($name, $owner);
        $this->persist($account);

        return $account;
    }

    private function createDeposit(
        DepositAccount $account,
        string $sum = '1000.00',
        int $type = self::DEPOSIT,
        string $date = '2025-01-15',
    ): Deposit {
        $deposit = new Deposit($sum, $type, $account->getUser(), $account, new \DateTime($date));
        $this->persist($deposit);

        return $deposit;
    }

    private static function daysAgo(int $days): string
    {
        return (new \DateTimeImmutable('today'))->modify(sprintf('-%d days', $days))->format('Y-m-d');
    }
}
