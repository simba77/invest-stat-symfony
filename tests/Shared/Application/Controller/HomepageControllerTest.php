<?php

declare(strict_types=1);

namespace App\Tests\Shared\Application\Controller;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\CurrencyRate;
use App\Tests\Deposits\CreatesDeposits;
use App\Tests\Support\ApiTestCase;

final class HomepageControllerTest extends ApiTestCase
{
    use CreatesDeposits;

    public function testDashboardRejectsAnonymousRequest(): void
    {
        $this->getJson('/api/dashboard');

        self::assertResponseStatusCodeSame(403);
    }

    public function testDashboardOfUserWithoutAnyData(): void
    {
        $this->loginAs($this->admin());

        $this->getJson('/api/dashboard');

        self::assertResponseIsSuccessful();
        /** @var array{usd: string, depositAccounts: list<mixed>, summary: list<array{name: string, total: string, percent?: string}>} $dashboard */
        $dashboard = $this->responseJson();
        self::assertSame('0', $dashboard['usd']);
        self::assertSame([], $dashboard['depositAccounts']);
        self::assertSame([
            'The Invested Amount'         => '0',
            'Deposits'                    => '0',
            'Deposits + Investments'      => '0.00',
            'All Assets'                  => '0',
            'Profit'                      => '0.00',
            'Saving + All Brokers Assets' => '0.00',
            'Blocked Assets'              => '0',
            'Liquid assets'               => '0.00',
        ], array_column($dashboard['summary'], 'total', 'name'));
        self::assertSame(
            ['All Assets' => '0', 'Profit' => '0'],
            array_column(array_filter($dashboard['summary'], static fn(array $item): bool => isset($item['percent'])), 'percent', 'name'),
        );
    }

    public function testDashboardSummarizesDeposits(): void
    {
        $admin = $this->admin();
        $bank = $this->createAccount($admin, 'Bank');
        $this->createDeposit($bank, '1000.00');
        $this->createDeposit($bank, '50.00', self::PERCENT);
        $closed = $this->createAccount($admin, 'Closed');
        $this->createDeposit($closed, '300.00');
        $this->createDeposit($closed, '-300.00');
        $this->createAccount($admin, 'Empty');
        $this->createDeposit($this->createAccount($this->otherUser()), '999.00');
        $this->persist(new CurrencyRate('RUB', 'USD', '95.5000', new \DateTimeImmutable('2026-01-15')));
        $this->loginAs($admin);

        $this->getJson('/api/dashboard');

        self::assertResponseIsSuccessful();
        /** @var array{usd: string, depositAccounts: list<mixed>, summary: list<array{name: string, total: string}>} $dashboard */
        $dashboard = $this->responseJson();
        self::assertSame('95.5000', $dashboard['usd']);
        self::assertSame(
            [['id' => $bank->getId(), 'name' => 'Bank', 'total' => 1050, 'profit' => 50]],
            $dashboard['depositAccounts'],
        );
        $totals = array_column($dashboard['summary'], 'total', 'name');
        self::assertSame('1050.00', $totals['Deposits']);
        self::assertSame('1050.00', $totals['Deposits + Investments']);
        self::assertSame('1050.00', $totals['Saving + All Brokers Assets']);
        self::assertSame('1050.00', $totals['Liquid assets']);
    }

    public function testDashboardCountsBlockedCashOfAnyAccount(): void
    {
        $admin = $this->admin();
        $account = new Account($admin, 'Broker', balance: '1000', usdBalance: '100');
        $account->setBlockedCash('RUB', '300');
        $account->setBlockedCash('USD', '40');
        $this->persist($account, new CurrencyRate('RUB', 'USD', '80', new \DateTimeImmutable('2026-01-15')));
        $this->loginAs($admin);

        $this->getJson('/api/dashboard');

        self::assertResponseIsSuccessful();
        /** @var array{summary: list<array{name: string, total: string}>} $dashboard */
        $dashboard = $this->responseJson();
        $totals = array_column($dashboard['summary'], 'total', 'name');
        // 1000 + 100 × 80 held, of which 300 + 40 × 80 are blocked
        self::assertSame('9000.00', $totals['All Assets']);
        self::assertSame('3500.00', $totals['Blocked Assets']);
        self::assertSame('5500.00', $totals['Liquid assets']);
    }
}
