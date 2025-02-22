<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Dividend;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<array{deals: mixed, dividends: Dividend[], coupons: Coupon[]}, array<string, string>>
 */
class MonthlyDealsListCompiler implements CompilerInterface
{
    public function __construct(
        public readonly CurrencyService $currencyService,
    ) {
    }

    /**
     * @param array{deals: mixed, dividends: Dividend[], coupons: Coupon[]} $entry
     * @return array<string, string>
     */
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry['deals'] as $deal) {
            $dealData = new DealData($deal, $deal['deal']->getAccount(), $this->currencyService);
            $date = $deal['deal']->getClosingDate()?->format('Y.m') ?? '0';

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
                $result[$date] = $dividend->getAmount();
            }
        }

        foreach ($entry['coupons'] as $coupon) {
            $date = $coupon->getDate()?->format('Y.m') ?? '0';
            if (isset($result[$date])) {
                $result[$date] = bcadd($result[$date], $coupon->getAmount(), 2);
            } else {
                $result[$date] = $coupon->getAmount();
            }
        }

        ksort($result);

        return $result;
    }
}
