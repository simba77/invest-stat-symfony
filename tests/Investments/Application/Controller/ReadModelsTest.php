<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Tests\Investments\PortfolioScenario;
use App\Tests\Support\ApiTestCase;
use App\Tests\Support\MatchesJsonSnapshots;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

/**
 * Characterization of every page that shows deals, payouts or account values: the snapshots
 * pin the numbers the SPA shows today, so that reworking the storage cannot change them unnoticed.
 */
final class ReadModelsTest extends ApiTestCase
{
    use ClockSensitiveTrait;
    use MatchesJsonSnapshots;
    use PortfolioScenario;

    /** @var array{main: Account, second: Account} */
    private array $accounts;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        static::mockTime('2026-03-02 09:00:00');
        $admin = $this->admin();
        $this->accounts = $this->createPortfolio($admin);
        $this->loginAs($admin);
    }

    public function testAccounts(): void
    {
        $this->getJson('/api/accounts');

        self::assertResponseIsSuccessful();
        $json = $this->responseJson();
        self::assertSame([$this->accounts['main']->getId(), $this->accounts['second']->getId()], self::idsAt($json, 'id'));
        self::assertMatchesJsonSnapshot('accounts', $json);
    }

    public function testAccountDeals(): void
    {
        $this->getJson('/api/deals/' . $this->accounts['main']->getId());

        self::assertResponseIsSuccessful();
        /** @var array{account: array<string, mixed>, deals: array<string, mixed>} $json */
        $json = $this->responseJson();
        self::assertSame($this->accounts['main']->getId(), $json['account']['id']);
        self::assertEqualsCanonicalizing(
            $this->dealIds('sber long', 'sber long 2', 'sber blocked', 'gazp short', 'aapl long', 'bond long'),
            self::idsAt($json['deals'], 'id'),
        );
        self::assertSame([$this->accounts['main']->getId()], array_values(array_unique(self::idsAt($json['deals'], 'accountId'))));
        $this->assertInstrumentIds($json['deals']);
        self::assertMatchesJsonSnapshot('account-deals', $json);
    }

    public function testPortfolio(): void
    {
        $this->getJson('/api/portfolio');

        self::assertResponseIsSuccessful();
        $json = $this->responseJson();
        self::assertEqualsCanonicalizing(
            $this->dealIds('sber long', 'sber long 2', 'sber blocked', 'gazp short', 'aapl long', 'bond long', 'gazp second'),
            self::idsAt($json, 'id'),
        );
        $this->assertInstrumentIds($json);
        self::assertMatchesJsonSnapshot('portfolio', $json);
    }

    public function testClosedDeals(): void
    {
        $this->getJson('/api/analytics/closed-deals');

        self::assertResponseIsSuccessful();
        $json = $this->responseJson();
        self::assertEqualsCanonicalizing(
            $this->dealIds('sber closed', 'aapl closed', 'future closed', 'gazp short closed', 'sber second'),
            self::idsAt($json, 'id'),
        );
        self::assertMatchesJsonSnapshot('closed-deals', $json);
    }

    public function testClosedDealsOfPeriod(): void
    {
        $this->getJson('/api/analytics/closed-deals?startDate=01.01.2026&endDate=31.01.2026');

        self::assertResponseIsSuccessful();
        $json = $this->responseJson();
        self::assertEqualsCanonicalizing($this->dealIds('aapl closed', 'sber second'), self::idsAt($json, 'id'));
        self::assertMatchesJsonSnapshot('closed-deals-of-january', $json);
    }

    public function testMonthlyProfit(): void
    {
        $this->getJson('/api/analytics/monthly-closed-deals');

        self::assertResponseIsSuccessful();
        self::assertMatchesJsonSnapshot('monthly-profit', $this->responseJson());
    }

    public function testDashboard(): void
    {
        $this->getJson('/api/dashboard');

        self::assertResponseIsSuccessful();
        self::assertMatchesJsonSnapshot('dashboard', $this->responseJson());
    }

    /**
     * @dataProvider instruments
     */
    public function testInstrumentPage(string $type, string $ticker): void
    {
        $instrument = $this->instruments[$ticker];

        $this->getJson(sprintf('/api/instrument/%s/%d', $type, (int) $instrument->getId()));

        self::assertResponseIsSuccessful();
        /** @var array{id: int} $json */
        $json = $this->responseJson();
        self::assertSame($instrument->getId(), $json['id']);
        self::assertMatchesJsonSnapshot('instrument-' . $ticker, $json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function instruments(): iterable
    {
        yield 'share' => ['share', 'SBER'];
        yield 'share in dollars' => ['share', 'AAPL'];
        yield 'bond' => ['bond', 'SU26238RMFS4'];
        yield 'future' => ['future', 'SiH6'];
    }

    /**
     * @dataProvider payoutPages
     */
    public function testRecordsPage(string $uri, string $snapshot): void
    {
        $this->getJson($uri);

        self::assertResponseIsSuccessful();
        /** @var array{items: list<array<string, mixed>>} $json */
        $json = $this->responseJson();
        self::assertNotContains('Stranger', array_column($json['items'], 'accountName'));
        self::assertNotContains('Stranger', array_column($json['items'], 'account'));
        self::assertMatchesJsonSnapshot($snapshot, $json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function payoutPages(): iterable
    {
        yield 'dividends' => ['/api/dividends', 'dividends'];
        yield 'coupons' => ['/api/coupons', 'coupons'];
        yield 'deposits' => ['/api/investments', 'deposits'];
    }

    /**
     * @return list<int|null>
     */
    private function dealIds(string ...$names): array
    {
        return array_map(fn (string $name) => $this->deals[$name]->getId(), $names);
    }

    /**
     * Group rows link to the instrument page of their ticker.
     */
    private function assertInstrumentIds(mixed $json): void
    {
        $instrumentIds = array_map(static fn ($instrument) => $instrument->getId(), $this->instruments);
        foreach (self::idsAt($json, 'instrumentId') as $id) {
            self::assertContains($id, $instrumentIds);
        }
    }
}
