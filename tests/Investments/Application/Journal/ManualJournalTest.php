<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Journal;

use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Operations\Deal;
use App\Tests\Investments\PortfolioScenario;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

final class ManualJournalTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use InteractsWithDatabase;
    use PortfolioScenario;

    public function testStartingJournalKeepsDealsAndCash(): void
    {
        static::mockTime('2026-03-02 09:00:00');
        $accounts = $this->createPortfolio($this->admin());
        $before = $this->describeDeals($accounts['main']);
        $main = $this->findFresh(Account::class, $accounts['main']->getId()) ?? self::fail();

        $this->journal()->rebuild($main);

        $main = $this->findFresh(Account::class, $main->getId());
        self::assertNotNull($main);
        self::assertSame('2026-03-02 09:00:00', $main->getJournalStartedAt()?->format('Y-m-d H:i:s'));
        self::assertSame($before, $this->describeDeals($main));
        self::assertSame(['RUB' => '50000.0000', 'USD' => '100.0000'], $main->getCashByCurrency());
        self::assertSame(
            ['block' => 1, 'buy' => 8, 'cash_adjustment' => 2, 'close' => 4, 'short' => 2],
            $this->operationCounts($main),
        );
    }

    public function testStartingJournalKeepsBlockedCash(): void
    {
        $account = $this->createAccount($this->admin(), balance: '1000');
        $account->setUsdBalance('3620');
        $account->setBlockedCash('USD', '3620');
        $this->persist($account);

        $this->journal()->rebuild($account);

        $account = $this->findFresh(Account::class, $account->getId());
        self::assertSame(['USD' => '3620.0000'], $account?->getBlockedCashByCurrency());
        self::assertSame(['block_cash' => 1, 'cash_adjustment' => 2], $this->operationCounts($account ?? self::fail()));
    }

    public function testStartedJournalIsNotStartedAgain(): void
    {
        $accounts = $this->createPortfolio($this->admin());
        $this->journal()->rebuild($accounts['second']);
        $operations = $this->operationCounts($accounts['second']);
        self::assertNotSame([], $operations);

        $this->journal()->rebuild($this->findFresh(Account::class, $accounts['second']->getId()) ?? self::fail());

        self::assertSame($operations, $this->operationCounts($accounts['second']));
    }

    public function testRebuildCommandStartsManualAccountsOnly(): void
    {
        $accounts = $this->createPortfolio($this->admin());
        $command = new CommandTester((new Application(static::bootKernel()))->find('accounts:rebuild'));

        $command->execute([]);

        $command->assertCommandIsSuccessful();
        foreach ($accounts as $account) {
            self::assertNotNull($this->findFresh(Account::class, $account->getId())?->getJournalStartedAt());
        }
    }

    private function journal(): ManualJournal
    {
        return static::getContainer()->get(ManualJournal::class);
    }

    /**
     * @return array<int, list<mixed>> by deal id
     */
    private function describeDeals(Account $account): array
    {
        $deals = [];
        foreach ($this->findFreshBy(Deal::class, ['account' => $account->getId()]) as $deal) {
            $deals[(int) $deal->getId()] = [
                $deal->getTicker(),
                $deal->getInstrument()?->getId(),
                $deal->getStatus(),
                $deal->getType(),
                $deal->getQuantity(),
                $deal->getBuyPrice(),
                $deal->getSellPrice(),
                $deal->getTargetPrice(),
                $deal->getClosingDate()?->format('Y-m-d H:i:s'),
                $deal->createdAt()->format('Y-m-d H:i:s'),
                $deal->getBuyCommission(),
                $deal->getSellCommission(),
            ];
        }

        return $deals;
    }

    /**
     * @return array<string, int>
     */
    private function operationCounts(Account $account): array
    {
        $counts = [];
        foreach ($this->findFreshBy(ManualOperation::class, ['account' => $account->getId()]) as $operation) {
            $counts[$operation->getType()->value] = ($counts[$operation->getType()->value] ?? 0) + 1;
        }
        ksort($counts);

        return $counts;
    }
}
