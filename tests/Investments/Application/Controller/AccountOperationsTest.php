<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Deal;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * The operations list shows the journal of a manual account, the latest operations first.
 */
final class AccountOperationsTest extends ApiTestCase
{
    use ClockSensitiveTrait;
    use CreatesInvestmentRecords;

    public function testListsJournalLatestFirst(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '10000');
        $account->setCommission('0.1');
        $this->persist($account);
        $this->createShare('SBER', price: '300');
        $this->loginAs($admin);

        static::mockTime('2026-03-02 09:00:00');
        $this->postJson('/api/deals/create/' . $account->getId(), ['ticker' => 'SBER', 'stockMarket' => 'MOEX', 'quantity' => 10, 'buyPrice' => '250', 'targetPrice' => '0', 'isShort' => false]);
        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        static::mockTime('2026-03-03 10:00:00');
        $this->postJson('/api/deals/block/' . $deal->getId());
        static::mockTime('2026-03-04 11:00:00');
        $this->postJson('/api/deals/unblock/' . $deal->getId());
        static::mockTime('2026-03-05 12:00:00');
        $this->postJson('/api/deals/sell', ['id' => $deal->getId(), 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 10]);
        static::mockTime('2026-03-06 13:00:00');
        $this->postJson('/api/accounts/update/' . $account->getId(), [
            'name' => 'Broker', 'balance' => '5000', 'blockedBalance' => '1000', 'usdBalance' => '0', 'blockedUsdBalance' => '0',
            'commission' => '0.1', 'futuresCommission' => '0', 'sort' => 100,
        ]);
        self::assertResponseIsSuccessful();

        $this->getJson('/api/accounts/' . $account->getId() . '/operations?perPage=4');

        self::assertResponseIsSuccessful();
        /** @var array{items: list<array<string, mixed>>, pagination: array<string, mixed>} $page */
        $page = $this->responseJson();
        self::assertSame(['page' => 1, 'perPage' => 4, 'totalItems' => 7, 'totalPages' => 2, 'hasPrev' => false, 'hasNext' => true], $page['pagination']);
        self::assertSame([
            ['block_cash', '2026-03-06T13:00:00+00:00', null, null, null, null, null, '1000.0000', 'RUB', null, null],
            ['cash_adjustment', '2026-03-06T13:00:00+00:00', null, null, null, null, null, '-5594.4000', 'RUB', null, null],
            ['close', '2026-03-05T12:00:00+00:00', 'SBER', 'share', 10, '310.0000', '3.1000', null, 'RUB', '2026-03-02T09:00:00+00:00', '250.0000'],
            ['unblock', '2026-03-04T11:00:00+00:00', 'SBER', 'share', null, null, null, null, 'RUB', '2026-03-02T09:00:00+00:00', '250.0000'],
        ], array_map(self::describe(...), $page['items']));

        $this->getJson('/api/accounts/' . $account->getId() . '/operations?perPage=4&page=2');

        /** @var array{items: list<array<string, mixed>>} $page */
        $page = $this->responseJson();
        self::assertSame([
            ['block', '2026-03-03T10:00:00+00:00', 'SBER', 'share', null, null, null, null, 'RUB', '2026-03-02T09:00:00+00:00', '250.0000'],
            ['buy', '2026-03-02T09:00:00+00:00', 'SBER', 'share', 10, '250.0000', '2.5000', null, 'RUB', null, null],
            ['cash_adjustment', '2026-03-02T09:00:00+00:00', null, null, null, null, null, '10000.0000', 'RUB', null, null],
        ], array_map(self::describe(...), $page['items']));
    }

    public function testDoesNotListJournalOfAnotherUser(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->getJson('/api/accounts/' . $account->getId() . '/operations');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, mixed> $item
     * @return list<mixed>
     */
    private static function describe(array $item): array
    {
        return [
            $item['type'], $item['executedAt'], $item['ticker'], $item['instrumentType'], $item['quantity'], $item['price'], $item['commission'],
            $item['amount'], $item['currency'], $item['lotOpenedAt'], $item['lotPrice'],
        ];
    }
}
