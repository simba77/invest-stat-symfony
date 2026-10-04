<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * Characterization of manual deals: how creating, selling, editing and deleting a deal
 * changes the deals and the cash of the account.
 */
final class DealsControllerTest extends ApiTestCase
{
    use ClockSensitiveTrait;
    use CreatesInvestmentRecords;

    private const string NOW = '2026-03-02 09:00:00';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        static::mockTime(self::NOW);
    }

    public function testCreatePaysForLongDealFromRoubleCashWithoutCommission(): void
    {
        $account = $this->manualAccount(balance: '10000');
        $sber = $this->createShare('SBER', price: '300');

        $this->postJson('/api/deals/create/' . $account->getId(), $this->dealPayload('SBER', 10, '250', target: '320'));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->responseJson());
        $deals = $this->findFreshBy(Deal::class, ['account' => $account->getId()]);
        self::assertCount(1, $deals);
        self::assertSame(
            ['SBER', 'MOEX', DealStatus::Active, DealType::Long, 10, '250.0000', '320.0000', '0.0000', $sber->getId(), null, null, self::NOW],
            $this->describe($deals[0]),
        );
        self::assertSame(['7500.0000', '0.0000', '3000.0000'], $this->cashAndAssets($account));
    }

    public function testCreateAddsProceedsOfShortDealToCash(): void
    {
        $account = $this->manualAccount(balance: '10000');
        $this->createShare('GAZP', price: '150');

        $this->postJson('/api/deals/create/' . $account->getId(), $this->dealPayload('GAZP', 20, '170', isShort: true));

        self::assertResponseIsSuccessful();
        self::assertSame(DealType::Short, $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0]->getType());
        // The short is valued like a long position: its market value is added to the assets
        self::assertSame(['13400.0000', '0.0000', '3000.0000'], $this->cashAndAssets($account));
    }

    public function testCreatePaysForDollarShareFromDollarCash(): void
    {
        $account = $this->manualAccount(balance: '10000', usdBalance: '1000');
        $this->createShare('AAPL', 'SPB', 'USD', '200');
        $this->createCurrencyRate('USD', '80');

        $this->postJson('/api/deals/create/' . $account->getId(), $this->dealPayload('AAPL', 3, '150', market: 'SPB'));

        self::assertResponseIsSuccessful();
        self::assertSame(['10000.0000', '550.0000', '48000.0000'], $this->cashAndAssets($account));
    }

    public function testCreatePaysForBondNominalPercentAndAccruedCoupon(): void
    {
        $account = $this->manualAccount(balance: '10000');
        $bond = new Bond('SU26238RMFS4', 'ОФЗ 26238', 'MOEX', 'RUB', '95', prevPrice: '94', shortName: 'ОФЗ 26238', lotSize: '1000', couponAccumulated: '12.5');
        $this->persist($bond);

        $this->postJson('/api/deals/create/' . $account->getId(), $this->dealPayload('SU26238RMFS4', 2, '98.5'));

        self::assertResponseIsSuccessful();
        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        self::assertSame(['98.5000', $bond->getId()], [$deal->getBuyPrice(), $deal->getBond()?->getId()]);
        // 2 × (1000 × 98.5% + 12.5 accrued coupon); the accrued coupon paid is not kept with the deal
        self::assertSame(['8005.0000', '0.0000', '1925.0000'], $this->cashAndAssets($account));
    }

    public function testCreateLeavesCashUntouchedForFuture(): void
    {
        $account = $this->manualAccount(balance: '10000');
        $this->persist(new Future('SiH6', 'Si-3.26', 'MOEX', 'RUB', '90000', prevPrice: '89000', lotSize: '1', stepPrice: '1'));

        $this->postJson('/api/deals/create/' . $account->getId(), $this->dealPayload('SiH6', 1, '85000'));

        self::assertResponseIsSuccessful();
        // A future adds its open profit, not its price, to the assets
        self::assertSame(['10000.0000', '0.0000', '4995.0000'], $this->cashAndAssets($account));
    }

    /**
     * @dataProvider unknownSecurities
     *
     * @param list<string> $cash
     */
    public function testCreateGuessesCurrencyOfUnknownSecurityByMarket(string $market, array $cash): void
    {
        $account = $this->manualAccount(balance: '10000', usdBalance: '1000');

        $this->postJson('/api/deals/create/' . $account->getId(), $this->dealPayload('UNKNOWN', 4, '50', market: $market));

        self::assertResponseIsSuccessful();
        $deal = $this->findFreshBy(Deal::class, ['account' => $account->getId()])[0];
        self::assertSame([null, null, null], [$deal->getShare(), $deal->getBond(), $deal->getFuture()]);
        self::assertSame($cash, array_slice($this->cashAndAssets($account), 0, 2));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function unknownSecurities(): iterable
    {
        yield 'on MOEX in roubles' => ['MOEX', ['9800.0000', '1000.0000']];
        yield 'elsewhere in dollars' => ['SPB', ['10000.0000', '800.0000']];
    }

    public function testSellOneClosesDealAndAddsProceedsToCash(): void
    {
        $account = $this->manualAccount(balance: '7500');
        $deal = $this->openDeal($account, $this->createShare('SBER', price: '300'), 10, '250');

        $this->postJson('/api/deals/sell', ['id' => $deal->getId(), 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 10]);

        self::assertResponseIsSuccessful();
        $deal = $this->findFresh(Deal::class, $deal->getId());
        self::assertSame([DealStatus::Closed, 10, '310.0000', self::NOW], [$deal?->getStatus(), $deal?->getQuantity(), $deal?->getSellPrice(), $deal?->getClosingDate()?->format('Y-m-d H:i:s')]);
        self::assertSame(['10600.0000', '0.0000', '0.0000'], $this->cashAndAssets($account));
    }

    public function testSellOneOfShortDealPaysForBuyingBack(): void
    {
        $account = $this->manualAccount(balance: '13400');
        $deal = $this->openDeal($account, $this->createShare('GAZP', price: '150'), 20, '170', DealType::Short);

        $this->postJson('/api/deals/sell', ['id' => $deal->getId(), 'accountId' => $account->getId(), 'ticker' => 'GAZP', 'price' => '160', 'quantity' => 20]);

        self::assertResponseIsSuccessful();
        self::assertSame(DealStatus::Closed, $this->findFresh(Deal::class, $deal->getId())?->getStatus());
        self::assertSame(['10200.0000', '0.0000', '0.0000'], $this->cashAndAssets($account));
    }

    public function testSellOneOfFutureAddsPriceDifferenceWithoutMultiplier(): void
    {
        $account = $this->manualAccount(balance: '10000');
        $future = new Future('SiH6', 'Si-3.26', 'MOEX', 'RUB', '90000', prevPrice: '89000', lotSize: '1', stepPrice: '1');
        $future->setMultiplier('10');
        $this->persist($future);
        $deal = $this->openDeal($account, $future, 1, '85000');

        $this->postJson('/api/deals/sell', ['id' => $deal->getId(), 'accountId' => $account->getId(), 'ticker' => 'SiH6', 'price' => '88000', 'quantity' => 1]);

        self::assertResponseIsSuccessful();
        self::assertSame(['13000.0000', '0.0000', '0.0000'], $this->cashAndAssets($account));
    }

    public function testSellAsNeededClosesOldestActiveDealsAndSplitsTheLastOne(): void
    {
        $account = $this->manualAccount(balance: '0');
        $sber = $this->createShare('SBER', price: '300');
        $blocked = $this->openDeal($account, $sber, 4, '200', status: DealStatus::Blocked, openedAt: '2022-01-20 12:00:00');
        $first = $this->openDeal($account, $sber, 10, '250', openedAt: '2025-06-10 10:00:00', target: '320');
        $second = $this->openDeal($account, $sber, 5, '280', openedAt: '2025-09-15 11:30:00');

        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 12]);

        self::assertResponseIsSuccessful();
        $deals = $this->findFreshBy(Deal::class, ['account' => $account->getId()]);
        self::assertSame([$blocked->getId(), $first->getId(), $second->getId()], array_slice(array_map(static fn (Deal $deal) => $deal->getId(), $deals), 0, 3));
        self::assertSame(
            [
                [DealStatus::Blocked, 4, '200.0000', '0.0000', null, '2022-01-20 12:00:00'],
                [DealStatus::Closed, 10, '250.0000', '310.0000', self::NOW, '2025-06-10 10:00:00'],
                [DealStatus::Closed, 2, '280.0000', '310.0000', self::NOW, '2025-09-15 11:30:00'],
                // The rest of the split deal is a new deal that counts as opened at the sale
                [DealStatus::Active, 3, '280.0000', '0.0000', null, self::NOW],
            ],
            array_map(static fn (Deal $deal) => [
                $deal->getStatus(),
                $deal->getQuantity(),
                $deal->getBuyPrice(),
                $deal->getSellPrice(),
                $deal->getClosingDate()?->format('Y-m-d H:i:s'),
                $deal->createdAt()->format('Y-m-d H:i:s'),
            ], $deals),
        );
        self::assertSame(['3720.0000', '0.0000', '2100.0000'], $this->cashAndAssets($account));
    }

    public function testSellAsNeededRecordsClosedDealForSecuritiesThatWereNotHeld(): void
    {
        $account = $this->manualAccount(balance: '0');
        $this->openDeal($account, $this->createShare('SBER', price: '300'), 5, '250');

        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 8]);

        self::assertResponseIsSuccessful();
        self::assertSame(
            [[DealStatus::Closed, 5, '250.0000'], [DealStatus::Closed, 3, '250.0000']],
            array_map(
                static fn (Deal $deal) => [$deal->getStatus(), $deal->getQuantity(), $deal->getBuyPrice()],
                $this->findFreshBy(Deal::class, ['account' => $account->getId()]),
            ),
        );
        self::assertSame(['2480.0000', '0.0000', '0.0000'], $this->cashAndAssets($account));
    }

    public function testSellAsNeededFailsWithoutActiveDeals(): void
    {
        $account = $this->manualAccount(balance: '0');
        $this->createShare('SBER');

        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 8]);

        self::assertResponseStatusCodeSame(500);
    }

    public function testEditChangesDealButNotCash(): void
    {
        $account = $this->manualAccount(balance: '7500');
        $deal = $this->openDeal($account, $this->createShare('SBER', price: '300'), 10, '250');

        $this->postJson('/api/deals/edit/' . $deal->getId(), $this->dealPayload('SBER', 20, '240', target: '330'));

        self::assertResponseIsSuccessful();
        $deal = $this->findFresh(Deal::class, $deal->getId());
        self::assertSame([20, '240.0000', '330.0000'], [$deal?->getQuantity(), $deal?->getBuyPrice(), $deal?->getTargetPrice()]);
        self::assertSame(['7500.0000', '0.0000', '6000.0000'], $this->cashAndAssets($account));
    }

    public function testDeleteRemovesDealButNotItsCash(): void
    {
        $account = $this->manualAccount(balance: '7500');
        $deal = $this->openDeal($account, $this->createShare('SBER', price: '300'), 10, '250');

        $this->postJson('/api/deals/delete/' . $deal->getId());

        self::assertResponseIsSuccessful();
        self::assertNull($this->findFresh(Deal::class, $deal->getId()));
        self::assertSame(['7500.0000', '0.0000', '0.0000'], $this->cashAndAssets($account));
    }

    public function testShowReturnsDealForm(): void
    {
        $account = $this->manualAccount(balance: '0');
        $deal = $this->openDeal($account, $this->createShare('GAZP'), 20, '170', DealType::Short, target: '140');

        $this->getJson('/api/deals/get-by-id/' . $deal->getId());

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['deal' => ['id' => $deal->getId(), 'ticker' => 'GAZP', 'stockMarket' => 'MOEX', 'quantity' => 20, 'buyPrice' => '170.0000', 'targetPrice' => '140.0000', 'isShort' => true]],
            $this->responseJson(),
        );
    }

    public function testSellRejectsOtherUsersAccount(): void
    {
        $other = $this->otherUser();
        $account = $this->createAccount($other);
        $deal = new Deal($other, $account, 'SBER', 'MOEX', DealStatus::Active, DealType::Long, 10, '300');
        $this->persist($deal);
        $this->loginAs($this->admin());

        $this->postJson('/api/deals/sell', ['id' => null, 'accountId' => $account->getId(), 'ticker' => 'SBER', 'price' => '310', 'quantity' => 10]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(DealStatus::Active, $this->findFresh(Deal::class, $deal->getId())?->getStatus());
    }

    /**
     * An account of the admin with the commission of 0.3%, and the admin logged in.
     */
    private function manualAccount(string $balance, string $usdBalance = '0'): Account
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, 'Manual', $balance);
        $account->setUsdBalance($usdBalance);
        $account->setCommission('0.3');
        $this->persist($account);
        $this->loginAs($admin);

        return $account;
    }

    private function openDeal(
        Account $account,
        Share|Bond|Future $instrument,
        int $quantity,
        string $buyPrice,
        DealType $type = DealType::Long,
        DealStatus $status = DealStatus::Active,
        string $openedAt = '2025-01-01 10:00:00',
        string $target = '0',
    ): Deal {
        $deal = new Deal($this->owner($account), $account, $instrument->getTicker(), $instrument->getStockMarket(), $status, $type, $quantity, $buyPrice, $target);
        $deal->setInstrument($instrument);
        $this->persist($deal);
        $deal->wasCreatedAt(new \DateTimeImmutable($openedAt));
        $this->persist($deal);

        return $deal;
    }

    /**
     * @return array<string, mixed>
     */
    private function dealPayload(string $ticker, int $quantity, string $buyPrice, string $target = '0', bool $isShort = false, string $market = 'MOEX'): array
    {
        return ['ticker' => $ticker, 'stockMarket' => $market, 'quantity' => $quantity, 'buyPrice' => $buyPrice, 'targetPrice' => $target, 'isShort' => $isShort];
    }

    /**
     * @return list<mixed>
     */
    private function describe(Deal $deal): array
    {
        return [
            $deal->getTicker(),
            $deal->getStockMarket(),
            $deal->getStatus(),
            $deal->getType(),
            $deal->getQuantity(),
            $deal->getBuyPrice(),
            $deal->getTargetPrice(),
            $deal->getSellPrice(),
            $deal->getShare()?->getId(),
            $deal->getBond()?->getId(),
            $deal->getFuture()?->getId(),
            $deal->createdAt()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Rouble and dollar cash, and the current value of the deals as last recalculated.
     *
     * @return list<string>
     */
    private function cashAndAssets(Account $account): array
    {
        $account = $this->findFresh(Account::class, $account->getId());

        return [(string) $account?->getBalance(), (string) $account?->getUsdBalance(), (string) $account?->getCurrentSumOfAssets()];
    }
}
