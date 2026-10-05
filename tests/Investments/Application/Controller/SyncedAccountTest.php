<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\Investment;
use App\Tests\Investments\BrokerSync\CreatesBrokerLinks;
use App\Tests\Support\ApiTestCase;

/**
 * The broker sync owns the records of a linked account; changes by hand are refused.
 */
final class SyncedAccountTest extends ApiTestCase
{
    use CreatesBrokerLinks;

    /**
     * @dataProvider manualChanges
     *
     * @param array<string, mixed> $payload
     */
    public function testRefusesManualRecordsOnSyncedAccount(string $uri, array $payload): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, 'Autofollow');
        $this->linkAccount($account);
        $this->loginAs($admin);

        $this->postJson(
            str_replace('{account}', (string) $account->getId(), $uri),
            $payload + ['accountId' => $account->getId(), 'account' => $account->getId()],
        );

        self::assertResponseStatusCodeSame(409);
        /** @var array{message: string} $body */
        $body = $this->responseJson();
        self::assertStringContainsString('Account "Autofollow" is synced with a broker', $body['message']);
        self::assertSame([], $this->findFreshBy(Deal::class, ['account' => $account->getId()]));
        self::assertSame([], $this->findFreshBy(Investment::class, ['account' => $account->getId()]));
        self::assertSame([], $this->findFreshBy(Dividend::class, ['account' => $account->getId()]));
        self::assertSame([], $this->findFreshBy(Coupon::class, ['account' => $account->getId()]));
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function manualChanges(): iterable
    {
        yield 'deal' => ['/api/deals/create/{account}', ['ticker' => 'SBER', 'stockMarket' => 'MOEX', 'quantity' => 10, 'buyPrice' => '300', 'targetPrice' => '0', 'isShort' => false]];
        yield 'deposit' => ['/api/investments/create', ['date' => '2026-01-01', 'sum' => '1000']];
        yield 'dividend' => ['/api/dividends/create', ['amount' => '100', 'ticker' => 'SBER', 'stockMarket' => 'MOEX', 'date' => '2026-01-01']];
        yield 'coupon' => ['/api/coupons/create', ['amount' => '100', 'ticker' => 'SU26238RMFS4', 'stockMarket' => 'MOEX', 'date' => '2026-01-01']];
    }

    public function testRefusesToDeleteSyncedDeal(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account);
        $deal = new Deal($account, 'SBER', 'MOEX', DealStatus::Active, DealType::Long, 10, '300');
        $this->persist($deal);
        $this->loginAs($admin);

        $this->postJson('/api/deals/delete/' . $deal->getId());

        self::assertResponseStatusCodeSame(409);
        self::assertCount(1, $this->findFreshBy(Deal::class, ['account' => $account->getId()]));
    }

    public function testKeepsBrokerCashWhenAccountIsEdited(): void
    {
        $admin = $this->admin();
        $account = new Account($admin, 'Autofollow', balance: '1134.78');
        $this->persist($account);
        $this->linkAccount($account);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/update/' . $account->getId(), [
            'name'              => 'T-Bank autofollow',
            'balance'           => '79490',
            'usdBalance'        => '0',
            'commission'        => '0',
            'futuresCommission' => '0',
            'sort'              => 100,
        ]);

        self::assertResponseIsSuccessful();
        $account = $this->findFresh(Account::class, $account->getId());
        self::assertSame('T-Bank autofollow', $account?->getName());
        self::assertSame('1134.7800', $account?->getBalance());
    }

    public function testMarksSyncedAccountsInList(): void
    {
        $admin = $this->admin();
        $synced = $this->createAccount($admin, 'Synced');
        $this->linkAccount($synced);
        $manual = $this->createAccount($admin, 'Manual');
        $this->loginAs($admin);

        $this->getJson('/api/accounts');

        self::assertResponseIsSuccessful();
        /** @var list<array{id: int, isSynced: bool}> $accounts */
        $accounts = $this->responseJson();
        self::assertSame(
            [$synced->getId() => true, $manual->getId() => false],
            array_column($accounts, 'isSynced', 'id'),
        );
    }

    public function testRefusesToCloseSyncedAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account);
        $this->loginAs($admin);

        $this->postJson('/api/accounts/close/' . $account->getId());

        self::assertResponseStatusCodeSame(409);
        self::assertFalse($this->findFresh(Account::class, $account->getId())?->isClosed());
    }

    public function testMarksSyncedAccountInEditForm(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $this->linkAccount($account);
        $this->loginAs($admin);

        $this->getJson('/api/accounts/get-form/' . $account->getId());

        self::assertResponseIsSuccessful();
        /** @var array{isSynced: bool} $form */
        $form = $this->responseJson();
        self::assertTrue($form['isSynced']);
    }
}
