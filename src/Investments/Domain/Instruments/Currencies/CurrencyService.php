<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

use App\Investments\Domain\Instruments\CurrencyRateRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Rates of currencies in roubles: the latest one values what is held now,
 * the one of an exchange day values what happened on that day.
 */
class CurrencyService implements ResetInterface
{
    /** Exchange days are counted in Moscow time. */
    private const string TIMEZONE = 'Europe/Moscow';

    /** @var array<string, string> latest rate by currency */
    private array $latest = [];

    /** @var array<string, list<array{date: string, rate: numeric-string}>> rates by currency, in date order */
    private array $history = [];

    public function __construct(
        private readonly CurrencyRateRepositoryInterface $currencyRateRepository,
    ) {
    }

    /**
     * Get RUB per USD rate
     */
    public function getUSDRUBRate(): string
    {
        return $this->getCurrencyRate('USD');
    }

    public function getCurrencyRate(string $targetCurrency = 'USD'): string
    {
        return $this->latest[$targetCurrency] ??= $this->currencyRateRepository->findLatest('RUB', $targetCurrency)?->getRate() ?? '0';
    }

    /**
     * The rate of the exchange day of the moment, or of the last day before it: there is none
     * on weekends and holidays. Before the first known day, the rate of that day is taken.
     *
     * @return numeric-string
     */
    public function getCurrencyRateOn(string $currency, \DateTimeInterface $moment): string
    {
        if ($currency === 'RUB') {
            return '1';
        }

        $history = $this->history($currency);
        if ($history === []) {
            /** @var numeric-string */
            return $this->getCurrencyRate($currency);
        }

        $day = \DateTimeImmutable::createFromInterface($moment)->setTimezone(new \DateTimeZone(self::TIMEZONE))->format('Y-m-d');
        // The last day not after the moment
        $low = 0;
        $high = count($history) - 1;
        $found = null;
        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            if ($history[$middle]['date'] <= $day) {
                $found = $middle;
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        return $history[$found ?? 0]['rate'];
    }

    #[\Override]
    public function reset(): void
    {
        $this->latest = [];
        $this->history = [];
    }

    /**
     * @return list<array{date: string, rate: numeric-string}>
     */
    private function history(string $currency): array
    {
        return $this->history[$currency] ??= array_map(
            static fn (array $row) => ['date' => $row['date']->format('Y-m-d'), 'rate' => $row['rate']],
            $this->currencyRateRepository->findHistory('RUB', $currency),
        );
    }
}
