<?php

declare(strict_types=1);

namespace App\Investments\Application\Instruments;

use App\Investments\Domain\Instruments\Currencies\CurrencyProviderInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyRateInterface;
use App\Investments\Domain\Instruments\CurrencyRate;
use App\Investments\Domain\Instruments\CurrencyRateRepositoryInterface;

/**
 * Keeps one rate per currency and exchange day: the current rates rewrite the rate of their day,
 * the history fills in the days before.
 */
final readonly class CurrencyRatesUpdater
{
    public function __construct(
        private CurrencyProviderInterface $provider,
        private CurrencyRateRepositoryInterface $repository,
    ) {
    }

    /**
     * @return int the number of days added or changed
     */
    public function updateCurrent(): int
    {
        return $this->store($this->provider->getCurrencyRates());
    }

    /**
     * @return int the number of days added or changed
     */
    public function loadHistory(\DateTimeImmutable $from, \DateTimeImmutable $till): int
    {
        return $this->store($this->provider->getRateHistory($from, $till));
    }

    /**
     * @param array<CurrencyRateInterface> $rates
     */
    private function store(array $rates): int
    {
        /** @var array<string, array<string, string>> $known rate by pair and day */
        $known = [];
        $changed = [];
        foreach ($rates as $rate) {
            $base = $rate->getBaseCurrency();
            $target = $rate->getTargetCurrency();
            $pair = $base . '/' . $target;
            $known[$pair] ??= $this->knownDays($base, $target);

            $day = $rate->getDate()->format('Y-m-d');
            $current = $known[$pair][$day] ?? null;
            if ($current !== null && is_numeric($rate->getRate()) && bccomp($current, $rate->getRate(), 4) === 0) {
                continue;
            }

            $entity = $current !== null ? $this->repository->findOnDate($base, $target, $rate->getDate()) : null;
            $changed[] = $entity?->setRate($rate->getRate()) ?? new CurrencyRate($base, $target, $rate->getRate(), $rate->getDate());
            $known[$pair][$day] = $rate->getRate();
        }

        $this->repository->save(...$changed);

        return count($changed);
    }

    /**
     * @return array<string, string> rate by day
     */
    private function knownDays(string $base, string $target): array
    {
        $days = [];
        foreach ($this->repository->findHistory($base, $target) as $row) {
            $days[$row['date']->format('Y-m-d')] = $row['rate'];
        }

        return $days;
    }
}
