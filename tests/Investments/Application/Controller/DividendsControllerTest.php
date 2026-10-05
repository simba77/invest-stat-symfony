<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Accounts\Account;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class DividendsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testCreateWithholdsTaxByOwnerProfileAndAddsToCash(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '1000');
        $sber = $this->createShare('SBER');
        $this->loginAs($admin);

        $this->postJson('/api/dividends/create', [
            'accountId'   => $account->getId(),
            'amount'      => '87',
            'ticker'      => 'SBER',
            'stockMarket' => 'MOEX',
            'date'        => '2026-01-15',
        ]);

        self::assertResponseIsSuccessful();
        $dividends = $this->findFreshBy(Dividend::class, ['account' => $account->getId()]);
        self::assertCount(1, $dividends);
        // The amount is what reached the account; the tax is added on top by the 13% rate
        self::assertSame(['87.0000', '13.0000', '2026-01-15'], [$dividends[0]->getAmount(), $dividends[0]->getTax(), $dividends[0]->getDate()?->format('Y-m-d')]);
        self::assertSame($sber->getId(), $dividends[0]->getShare()?->getId());
        self::assertSame('1087.0000', $this->findFresh(Account::class, $account->getId())?->getBalance());
    }

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
