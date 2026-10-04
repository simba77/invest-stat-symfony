<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Dividend;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class DividendsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testCreateRejectsOtherUsersAccount(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->postJson('/api/dividends/create', [
            'accountId'   => $account->getId(),
            'amount'      => '100',
            'ticker'      => 'SBER',
            'stockMarket' => 'MOEX',
            'date'        => '2026-01-15',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->findFreshBy(Dividend::class, ['account' => $account->getId()]));
    }

    public function testEditDoesNotMoveDividendToOtherUsersAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $dividend = $this->createDividend($account, amount: '100');
        $othersAccount = $this->createAccount($this->otherUser());
        $this->loginAs($admin);

        $this->postJson('/api/dividends/update/' . $dividend->getId(), [
            'accountId'   => $othersAccount->getId(),
            'amount'      => '200',
            'ticker'      => 'SBER',
            'stockMarket' => 'MOEX',
            'date'        => '2026-01-15',
        ]);

        self::assertResponseStatusCodeSame(404);
        $dividend = $this->findFresh(Dividend::class, $dividend->getId());
        self::assertSame($account->getId(), $dividend?->getAccount()?->getId());
        self::assertSame('100.0000', $dividend?->getAmount());
    }
}
