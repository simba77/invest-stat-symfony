<?php

declare(strict_types=1);

namespace App\Tests\Investments\Domain\Instruments\Currencies;

use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\CurrencyRate;
use App\Investments\Domain\Instruments\CurrencyRateRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CurrencyServiceTest extends TestCase
{
    /**
     * @dataProvider moments
     */
    public function testTakesRateOfTheExchangeDayOfTheMoment(string $moment, string $rate): void
    {
        $service = $this->serviceWith(
            new CurrencyRate('RUB', 'USD', '75', new \DateTimeImmutable('2026-01-15')),
            new CurrencyRate('RUB', 'USD', '78', new \DateTimeImmutable('2026-01-16')),
            new CurrencyRate('RUB', 'USD', '80', new \DateTimeImmutable('2026-01-19')),
        );

        self::assertSame($rate, $service->getCurrencyRateOn('USD', new \DateTimeImmutable($moment)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function moments(): iterable
    {
        yield 'a day with a rate' => ['2026-01-16 12:00:00+03:00', '78'];
        yield 'a weekend takes the friday' => ['2026-01-18 12:00:00+03:00', '78'];
        yield 'the day is counted in Moscow time' => ['2026-01-15 22:30:00+00:00', '78'];
        yield 'after the last known day' => ['2026-03-01 12:00:00+03:00', '80'];
        yield 'before the first known day' => ['2025-12-01 12:00:00+03:00', '75'];
    }

    public function testRoubleCostsOneRouble(): void
    {
        self::assertSame('1', $this->serviceWith()->getCurrencyRateOn('RUB', new \DateTimeImmutable('2026-01-16')));
    }

    public function testCurrentRateIsTheRateOfTheLatestDay(): void
    {
        $service = $this->serviceWith(
            new CurrencyRate('RUB', 'USD', '75', new \DateTimeImmutable('2026-01-15')),
            new CurrencyRate('RUB', 'USD', '80', new \DateTimeImmutable('2026-01-19')),
        );

        self::assertSame('80', $service->getCurrencyRate('USD'));
        self::assertSame('0', $service->getCurrencyRate('EUR'));
        self::assertSame('0', $service->getCurrencyRateOn('EUR', new \DateTimeImmutable('2026-01-16')));
    }

    private function serviceWith(CurrencyRate ...$rates): CurrencyService
    {
        return new CurrencyService(new class ($rates) implements CurrencyRateRepositoryInterface {
            /**
             * @param list<CurrencyRate> $rates in date order
             */
            public function __construct(private array $rates)
            {
            }

            public function findLatest(string $baseCurrency, string $targetCurrency): ?CurrencyRate
            {
                $rates = $this->findHistory($baseCurrency, $targetCurrency);
                if ($rates === []) {
                    return null;
                }
                $latest = end($rates);

                return new CurrencyRate($baseCurrency, $targetCurrency, $latest['rate'], $latest['date']);
            }

            public function findOnDate(string $baseCurrency, string $targetCurrency, \DateTimeImmutable $date): ?CurrencyRate
            {
                return null;
            }

            public function findHistory(string $baseCurrency, string $targetCurrency): array
            {
                return $targetCurrency === 'USD'
                    ? array_map(static fn (CurrencyRate $rate) => ['date' => $rate->getDate(), 'rate' => $rate->getRate()], $this->rates)
                    : [];
            }

            public function save(CurrencyRate ...$rates): void
            {
            }
        });
    }
}
