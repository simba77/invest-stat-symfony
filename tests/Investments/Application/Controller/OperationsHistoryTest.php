<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Tests\Investments\BrokerSync\CreatesBrokerLinks;
use App\Tests\Investments\BrokerSync\Operations;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * The history shows the operations of all the accounts of the user at once: the journals of the
 * manual accounts with their deposits and payouts, and what the broker reported for the synced ones.
 */
final class OperationsHistoryTest extends ApiTestCase
{
    use ClockSensitiveTrait;
    use CreatesBrokerLinks;

    public function testListsOperationsOfAllAccountsLatestFirst(): void
    {
        $this->givenAccounts();

        $this->getJson('/api/operations');

        self::assertResponseIsSuccessful();
        /** @var array{items: list<array<string, mixed>>, pagination: array{totalItems: int}} $page */
        $page = $this->responseJson();
        self::assertSame(8, $page['pagination']['totalItems']);
        self::assertSame([
            ['broker', 'Synced', '2026-03-04T11:00:00+00:00', 'Buy', 'trades', 'GAZP', 5, '170.0000', 'money', null, '-850.0000', 'RUB'],
            ['broker', 'Synced', '2026-03-03T10:00:00+00:00', 'Deposit', 'money', null, null, null, 'money', null, '5000.0000', 'RUB'],
            ['journal', 'Manual', '2026-03-02T09:00:00+00:00', 'Buy', 'trades', 'SU26238RMFS4', 2, '98.5000', 'percent', '1.9700', null, 'RUB'],
            ['journal', 'Manual', '2026-03-01T09:00:00+00:00', 'Buy', 'trades', 'SBER', 10, '250.0000', 'money', '2.5000', null, 'RUB'],
            // The journal started with the cash the account had
            ['journal', 'Manual', '2026-03-01T09:00:00+00:00', 'Cash adjustment', 'money', null, null, null, 'money', null, '10000.0000', 'RUB'],
            ['coupon', 'Manual', '2026-02-20T00:00:00+00:00', 'Coupon', 'payouts', 'SU26238RMFS4', null, null, 'money', null, '30.0000', 'RUB'],
            ['dividend', 'Manual', '2026-02-10T00:00:00+00:00', 'Dividend', 'payouts', 'SBER', null, null, 'money', null, '50.0000', 'RUB'],
            ['deposit', 'Manual', '2026-01-15T00:00:00+00:00', 'Deposit', 'money', null, null, null, 'money', null, '1000.0000', 'RUB'],
        ], array_map(self::describe(...), $page['items']));
    }

    public function testFiltersByCategoryAndAccount(): void
    {
        [, $synced] = $this->givenAccounts();

        $this->getJson('/api/operations?category=payouts');
        /** @var array{items: list<array{key: string, title: string}>} $page */
        $page = $this->responseJson();
        self::assertSame(['Coupon', 'Dividend'], array_column($page['items'], 'title'));

        $this->getJson('/api/operations?category=money&accountId=' . $synced->getId());
        /** @var array{items: list<array{key: string, title: string}>} $page */
        $page = $this->responseJson();
        self::assertSame(['Deposit'], array_column($page['items'], 'title'));
        self::assertStringStartsWith('broker:', $page['items'][0]['key']);
    }

    public function testPagesThroughAllSources(): void
    {
        $this->givenAccounts();

        $this->getJson('/api/operations?perPage=3&page=3');

        /** @var array{items: list<array{source: string}>, pagination: array{page: int, totalPages: int}} $page */
        $page = $this->responseJson();
        self::assertSame([3, 3], [$page['pagination']['page'], $page['pagination']['totalPages']]);
        self::assertSame(['dividend', 'deposit'], array_column($page['items'], 'source'));
    }

    /**
     * A manual account with two purchases, a deposit and payouts, a synced account with what the
     * broker reported, and an account of another user.
     *
     * @return array{0: Account, 1: Account}
     */
    private function givenAccounts(): array
    {
        $admin = $this->admin();
        $manual = $this->createAccount($admin, 'Manual', '10000');
        $manual->setCommission('0.1');
        $this->persist($manual);
        $this->createShare('SBER', price: '300');
        $this->createBond('SU26238RMFS4');
        $this->loginAs($admin);
        static::mockTime('2026-03-01 09:00:00');
        $this->postJson('/api/deals/create/' . $manual->getId(), ['ticker' => 'SBER', 'stockMarket' => 'MOEX', 'quantity' => 10, 'buyPrice' => '250', 'targetPrice' => '0', 'isShort' => false]);
        static::mockTime('2026-03-02 09:00:00');
        $this->postJson('/api/deals/create/' . $manual->getId(), ['ticker' => 'SU26238RMFS4', 'stockMarket' => 'MOEX', 'quantity' => 2, 'buyPrice' => '98.5', 'targetPrice' => '0', 'isShort' => false]);
        self::assertResponseIsSuccessful();
        // The requests leave the entities behind: load them again
        $manual = $this->findFresh(Account::class, $manual->getId());
        self::assertNotNull($manual);
        $admin = $this->admin();
        $this->createInvestment($manual, '1000', '2026-01-15');
        $this->createDividend($manual, 'SBER', '50', '2026-02-10');
        $this->createCoupon($manual, amount: '30', date: '2026-02-20');

        $synced = $this->createAccount($admin, 'Synced');
        $link = $this->linkAccount($synced);
        $this->persist(
            new BrokerOperation($link, Operations::deposit('d1', '2026-03-03 10:00:00', '5000')),
            new BrokerOperation($link, Operations::buy('b1', '2026-03-04 11:00:00', 'GAZP', 5, '170')),
            new BrokerOperation($link, Operations::buy('b2', '2026-03-05 11:00:00', 'GAZP', 1, '171', state: BrokerOperationState::Canceled)),
        );
        // The broker tells the payouts of a synced account
        $this->createDividend($synced, 'SBER', '70', '2026-02-11');

        $this->createInvestment($this->createAccount($this->otherUser(), 'Other'), '500', '2026-01-20');

        return [$manual, $synced];
    }

    /**
     * @param array<string, mixed> $item
     * @return list<mixed>
     */
    private static function describe(array $item): array
    {
        return [
            $item['source'], $item['accountName'], $item['executedAt'], $item['title'], $item['category'], $item['ticker'],
            $item['quantity'], $item['price'], $item['priceUnit'], $item['commission'], $item['amount'], $item['currency'],
        ];
    }
}
