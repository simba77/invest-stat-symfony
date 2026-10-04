<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Instruments\ShareRepositoryInterface;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Dividend;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array{deals: Deal[], dividends: Dividend[], coupons: Coupon[]}, array<string, string>>
 */
class MonthlyDealsListCompiler implements CompilerInterface
{
    public function __construct(
        public readonly CurrencyService $currencyService,
        public readonly FutureMultiplierRepositoryInterface $futureMultiplierRepository,
        private readonly ShareRepositoryInterface $shareRepository,
    ) {
    }

    /**
     * @param array{deals: Deal[], dividends: Dividend[], coupons: Coupon[]} $entry
     * @return array<string, string>
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        /** @var array<string, string> $result */
        $result = [];

        foreach ($entry['deals'] as $deal) {
            $dealData = new DealData($deal, $this->currencyService, $this->futureMultiplierRepository);
            $date = $deal->getClosingDate()?->format('Y.m') ?? '0';

            if (isset($result[$date])) {
                $result[$date] = bcadd($result[$date], $dealData->getProfitInBaseCurrency(), 2);
            } else {
                $result[$date] = $dealData->getProfitInBaseCurrency();
            }
        }

        /** @var array<string, string> $currencies share currency by market and ticker */
        $currencies = [];
        foreach ($entry['dividends'] as $dividend) {
            $date = $dividend->getDate()?->format('Y.m') ?? '0';
            $amount = $this->dividendInBaseCurrency($dividend, $currencies);
            if (isset($result[$date])) {
                $result[$date] = bcadd($result[$date], $amount, 2);
            } else {
                $result[$date] = $amount;
            }
        }

        // Coupons are recorded in roubles, as they reached the account, also for bonds in other currencies
        foreach ($entry['coupons'] as $coupon) {
            $date = $coupon->getDate()?->format('Y.m') ?? '0';
            if (isset($result[$date])) {
                $result[$date] = bcadd($result[$date], $coupon->getAmount(), 2);
            } else {
                $result[$date] = $coupon->getAmount() ?? '0';
            }
        }

        ksort($result);

        return $result;
    }

    /**
     * Dividends are recorded in the currency the share trades in, e.g. dollars on SPB,
     * and count in roubles at the rate of the day they were paid.
     *
     * @param array<string, string> $currencies
     */
    private function dividendInBaseCurrency(Dividend $dividend, array &$currencies): string
    {
        $amount = $dividend->getAmount() ?? '0';
        $key = $dividend->getStockMarket() . ':' . $dividend->getTicker();
        if (! isset($currencies[$key])) {
            $share = $this->shareRepository->findByTickerAndStockMarket((string) $dividend->getTicker(), (string) $dividend->getStockMarket());
            $currencies[$key] = $share?->getCurrency() ?? 'RUB';
        }

        if ($currencies[$key] === 'RUB') {
            return $amount;
        }

        $paidAt = $dividend->getDate();
        $rate = $paidAt !== null
            ? $this->currencyService->getCurrencyRateOn($currencies[$key], $paidAt)
            : $this->currencyService->getCurrencyRate($currencies[$key]);

        return bcmul($amount, $rate, 4);
    }
}
