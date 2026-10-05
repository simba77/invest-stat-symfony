<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Accounts\Account;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class InvestmentsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testCreateAddsDepositToCash(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '1000');
        $this->loginAs($admin);

        $this->postJson('/api/investments/create', ['account' => $account->getId(), 'sum' => '5000', 'date' => '2026-01-15']);

        self::assertResponseIsSuccessful();
        $deposits = $this->findFreshBy(Investment::class, ['account' => $account->getId()]);
        self::assertSame(['5000.00', '2026-01-15'], [$deposits[0]->getSum(), $deposits[0]->getDate()?->format('Y-m-d')]);
        self::assertSame('6000.0000', $this->findFresh(Account::class, $account->getId())?->getBalance());
    }

    public function testCreateRejectsOtherUsersAccount(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->postJson('/api/investments/create', ['account' => $account->getId(), 'sum' => '1000', 'date' => '2026-01-15']);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->findFreshBy(Investment::class, ['account' => $account->getId()]));
    }

    public function testEditDoesNotMoveDepositToOtherUsersAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $investment = $this->createInvestment($account, sum: '1000');
        $othersAccount = $this->createAccount($this->otherUser());
        $this->loginAs($admin);

        $this->postJson('/api/investments/edit/' . $investment->getId(), ['account' => $othersAccount->getId(), 'sum' => '5000', 'date' => '2026-01-15']);

        self::assertResponseStatusCodeSame(404);
        $investment = $this->findFresh(Investment::class, $investment->getId());
        self::assertSame($account->getId(), $investment?->getAccount()?->getId());
        self::assertSame('1000.00', $investment?->getSum());
    }
}
