<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Command;

use App\Investments\Domain\Instruments\CurrencyRate;
use App\Tests\Investments\Instruments\FakeCurrencyProvider;
use App\Tests\Support\InteractsWithDatabase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Console\Tester\CommandTester;

final class CurrencyRatesCommandsTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use InteractsWithDatabase;

    public function testCurrentRateRewritesTheRateOfItsDayAndStartsNextDay(): void
    {
        $this->persist(new CurrencyRate('RUB', 'USD', '80', new \DateTimeImmutable('2026-10-01')));
        $command = $this->command('currency:get-rates');
        $provider = $this->provider();

        $provider->current = [FakeCurrencyProvider::rate('USD', '83.2454', '2026-10-02')];
        $command->execute([]);
        $provider->current = [FakeCurrencyProvider::rate('USD', '83.4839', '2026-10-02')];
        $command->execute([]);
        $provider->current = [FakeCurrencyProvider::rate('USD', '83.10', '2026-10-05')];
        $command->execute([]);

        $command->assertCommandIsSuccessful();
        self::assertSame(
            ['2026-10-01 80.0000', '2026-10-02 83.4839', '2026-10-05 83.1000'],
            $this->days('USD'),
        );
    }

    public function testHistoryFillsInMissingDaysAndCorrectsChangedOnes(): void
    {
        static::mockTime('2026-10-04 04:00:00');
        $this->persist(
            new CurrencyRate('RUB', 'USD', '80', new \DateTimeImmutable('2026-09-29')),
            new CurrencyRate('RUB', 'USD', '81', new \DateTimeImmutable('2026-09-30')),
        );
        $command = $this->command('currency:get-rate-history');
        $provider = $this->provider();
        $provider->history = [
            FakeCurrencyProvider::rate('USD', '79', '2026-09-26'),
            FakeCurrencyProvider::rate('USD', '80', '2026-09-29'),
            FakeCurrencyProvider::rate('USD', '81.5', '2026-09-30'),
            FakeCurrencyProvider::rate('USD', '82', '2026-10-01'),
            FakeCurrencyProvider::rate('CNY', '11', '2026-10-01'),
        ];

        $command->execute(['--from' => '-7 days']);

        $command->assertCommandIsSuccessful();
        self::assertStringContainsString('Days added or changed: 3', $command->getDisplay());
        self::assertSame([['2026-09-27', '2026-10-04']], $provider->historyRequests);
        self::assertSame(['2026-09-29 80.0000', '2026-09-30 81.5000', '2026-10-01 82.0000'], $this->days('USD'));
        self::assertSame(['2026-10-01 11.0000'], $this->days('CNY'));
    }

    public function testHistoryStartsFromTheGivenDate(): void
    {
        static::mockTime('2026-10-04 04:00:00');
        $command = $this->command('currency:get-rate-history');

        $command->execute(['--from' => '2022-01-01']);

        $command->assertCommandIsSuccessful();
        self::assertSame([['2022-01-01', '2026-10-04']], $this->provider()->historyRequests);
    }

    public function testHistoryRejectsUnknownStart(): void
    {
        $command = $this->command('currency:get-rate-history');

        $command->execute(['--from' => 'the beginning']);

        self::assertSame(2, $command->getStatusCode());
        self::assertSame([], $this->provider()->historyRequests);
    }

    private function command(string $name): CommandTester
    {
        return new CommandTester((new Application(static::bootKernel()))->find($name));
    }

    private function provider(): FakeCurrencyProvider
    {
        /** @var FakeCurrencyProvider */
        return static::getContainer()->get(FakeCurrencyProvider::class);
    }

    /**
     * @return list<string>
     */
    private function days(string $currency): array
    {
        return array_map(
            static fn (CurrencyRate $rate) => $rate->getDate()->format('Y-m-d') . ' ' . $rate->getRate(),
            $this->findFreshBy(CurrencyRate::class, ['targetCurrency' => $currency]),
        );
    }
}
