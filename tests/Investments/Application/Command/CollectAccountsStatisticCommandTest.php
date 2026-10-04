<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Command;

use App\Tests\Investments\PortfolioScenario;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Characterization of the account snapshots behind the yearly statistics and the future intraday chart.
 */
final class CollectAccountsStatisticCommandTest extends KernelTestCase
{
    use InteractsWithDatabase;
    use PortfolioScenario;

    public function testRecordsValueOfEveryAccount(): void
    {
        $accounts = $this->createPortfolio($this->admin());
        $command = new CommandTester((new Application(static::bootKernel()))->find('accounts:save-stat'));

        $command->execute([]);

        $command->assertCommandIsSuccessful();
        $rows = $this->entityManager()->getConnection()->fetchAllAssociative(
            'SELECT account_id, balance, usd_balance, investments, current_value, profit FROM statistic
             WHERE account_id IN (?, ?) AND date > ? ORDER BY account_id',
            [$accounts['main']->getId(), $accounts['second']->getId(), '2026-03-01 23:59:59'],
        );
        self::assertSame(
            [
                [(string) $accounts['main']->getId(), '50000.00', '100.00', '80000.00', '116625.00', '36625.00'],
                [(string) $accounts['second']->getId(), '10000.00', '0.00', '15000.00', '11500.00', '-3500.00'],
            ],
            array_map(static fn (array $row) => array_map('strval', array_values($row)), $rows),
        );
    }
}
