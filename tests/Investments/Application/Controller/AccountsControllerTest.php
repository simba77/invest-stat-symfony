<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Analytics\Statistic;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class AccountsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testCreateStartsJournalWithTheCash(): void
    {
        $admin = $this->admin();
        $this->loginAs($admin);

        $this->postJson('/api/accounts/create', ['name' => 'New broker', 'balance' => '5000', 'usdBalance' => '10', 'commission' => '0.1', 'futuresCommission' => '0', 'sort' => 100]);

        self::assertResponseIsSuccessful();
        $accounts = $this->findFreshBy(Account::class, ['user' => $admin->getId(), 'name' => 'New broker']);
        self::assertCount(1, $accounts);
        self::assertNotNull($accounts[0]->getJournalStartedAt());
        self::assertSame(['RUB' => '5000.0000', 'USD' => '10.0000'], $accounts[0]->getCashByCurrency());
        self::assertSame(
            [['RUB', '5000.0000'], ['USD', '10.0000']],
            array_map(
                static fn (ManualOperation $operation) => [$operation->getCurrency(), $operation->getAmount()],
                $this->findFreshBy(ManualOperation::class, ['account' => $accounts[0]->getId(), 'type' => ManualOperationType::CashAdjustment]),
            ),
        );
    }

    public function testEditOfCashRecordsTheDifference(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '7500');
        $deal = new Deal($account, 'SBER', 'MOEX', DealStatus::Active, DealType::Long, 10, '250');
        $this->persist($deal);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/update/' . $account->getId(), ['name' => 'Broker', 'balance' => '9000', 'usdBalance' => '0', 'commission' => '0.1', 'futuresCommission' => '0', 'sort' => 100]);

        self::assertResponseIsSuccessful();
        self::assertSame('9000.0000', $this->findFresh(Account::class, $account->getId())?->getBalance());
        self::assertSame(10, $this->findFresh(Deal::class, $deal->getId())?->getQuantity());
        // The journal started from the deal and the cash, then the edit added 1500
        self::assertSame(
            ['10000.0000', '1500.0000'],
            array_map(
                static fn (ManualOperation $operation) => $operation->getAmount(),
                $this->findFreshBy(ManualOperation::class, ['account' => $account->getId(), 'type' => ManualOperationType::CashAdjustment, 'currency' => 'RUB']),
            ),
        );
    }

    public function testEditOfBlockedCashRecordsTheDifference(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '1000');
        $account->setUsdBalance('3000');
        $this->persist($account);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/update/' . $account->getId(), $this->accountForm(usdBalance: '3000', blockedUsdBalance: '1200'));
        $this->postJson('/api/accounts/update/' . $account->getId(), $this->accountForm(usdBalance: '3000', blockedUsdBalance: '1000'));

        self::assertResponseIsSuccessful();
        $account = $this->findFresh(Account::class, $account->getId());
        self::assertSame(['USD' => '1000.0000'], $account?->getBlockedCashByCurrency());
        self::assertSame('3000.0000', $account?->getUsdBalance());
        self::assertSame(
            ['1200.0000', '-200.0000'],
            array_map(
                static fn (ManualOperation $operation) => $operation->getAmount(),
                $this->findFreshBy(ManualOperation::class, ['account' => $account?->getId(), 'type' => ManualOperationType::BlockCash]),
            ),
        );

        $this->getJson('/api/accounts/get-form/' . (int) $account?->getId());
        /** @var array<string, mixed> $form */
        $form = $this->responseJson();
        self::assertSame(['0.0000', '1000.0000'], [$form['blockedBalance'], $form['blockedUsdBalance']]);
    }

    public function testEditRefusesNegativeBlockedCash(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/update/' . $account->getId(), $this->accountForm(blockedUsdBalance: '-1'));

        $this->assertViolatedFields(['blockedUsdBalance']);
    }

    public function testDeleteIsNotReachableByGet(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->loginAs($admin);

        $this->getJson('/api/accounts/delete/' . $account->getId());

        self::assertResponseStatusCodeSame(405);
        self::assertNotNull($this->findFresh(Account::class, $account->getId()));
    }

    public function testClosedAccountStaysInListMarked(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->createInvestment($account);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/close/' . $account->getId());

        self::assertResponseIsSuccessful();
        self::assertNotNull($this->findFresh(Account::class, $account->getId())?->getClosedAt());
        $this->getJson('/api/accounts');
        /** @var list<array{id: int, isClosed: bool, deposits: string}> $accounts */
        $accounts = $this->responseJson();
        self::assertSame([[$account->getId(), true, '1000.00']], array_map(static fn (array $item) => [$item['id'], $item['isClosed'], $item['deposits']], $accounts));

        $this->postJson('/api/accounts/reopen/' . $account->getId());

        self::assertResponseIsSuccessful();
        self::assertFalse($this->findFresh(Account::class, $account->getId())?->isClosed());
    }

    public function testDoesNotCloseAccountOfAnotherUser(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->postJson('/api/accounts/close/' . $account->getId());

        self::assertResponseStatusCodeSame(404);
        self::assertFalse($this->findFresh(Account::class, $account->getId())?->isClosed());
    }

    public function testDeletesEmptyAccountWithItsStatistics(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '100');
        $this->persist(new Statistic($account, new \DateTimeImmutable('2026-01-01'), '100', '0', '0', '100', '100'));
        $this->loginAs($admin);

        $this->postJson('/api/accounts/delete/' . $account->getId());

        self::assertResponseIsSuccessful();
        self::assertNull($this->findFresh(Account::class, $account->getId()));
        self::assertSame([], $this->findFreshBy(Statistic::class, ['account' => $account->getId()]));
    }

    public function testRefusesToDeleteAccountWithRecords(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->createCoupon($account);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/delete/' . $account->getId());

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $error */
        $error = $this->responseJson();
        self::assertStringContainsString('Only an empty account can be deleted', $error['message']);
        self::assertNotNull($this->findFresh(Account::class, $account->getId()));
    }

    /**
     * @return array<string, mixed>
     */
    private function accountForm(string $usdBalance = '0', string $blockedUsdBalance = '0'): array
    {
        return [
            'name'              => 'Broker',
            'balance'           => '1000',
            'blockedBalance'    => '0',
            'usdBalance'        => $usdBalance,
            'blockedUsdBalance' => $blockedUsdBalance,
            'commission'        => '0.1',
            'futuresCommission' => '0',
            'sort'              => 100,
        ];
    }
}
