<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
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
        public readonly FutureMultiplierRepositoryInterface $futureMultiplierRepository
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

        foreach ($entry['dividends'] as $dividend) {
            $date = $dividend->getDate()?->format('Y.m') ?? '0';
            if (isset($result[$date])) {
                $result[$date] = bcadd($result[$date], $dividend->getAmount(), 2);
            } else {
                $result[$date] = $dividend->getAmount() ?? '0';
            }
        }

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
}
