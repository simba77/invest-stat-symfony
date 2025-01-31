<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Investments\Domain\Instruments\Currencies\CurrencyService;

class AccountCalculator
{
    public function __construct(
        private readonly CurrencyService $currencyService
    ) {
    }

    /**
     * Balance in all currencies + all deals
     */
    public function getAccountValue(Account $account): string
    {
        $usdRate = $this->currencyService->getUSDRUBRate();
        $currentValue = bcadd($account->getCurrentSumOfAssets(), $account->getBalance(), 2);
        $usdBalance = bcmul($account->getUsdBalance(), $usdRate, 2);
        return bcadd($currentValue, $usdBalance, 2);
    }
}
