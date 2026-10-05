<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
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
        $accounts = $this->findFreshBy(Account::class, ['userId' => $admin->getId(), 'name' => 'New broker']);
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
