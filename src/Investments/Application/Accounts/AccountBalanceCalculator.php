<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;

/**
 * The value of an account now: its money and its open deals at the current prices and rates.
 * Computed on every request instead of being kept on the account.
 */
class AccountBalanceCalculator
{
    public function __construct(
        protected readonly DealRepositoryInterface $dealRepository,
        protected readonly CurrencyService $currencyService,
    ) {
    }

    /**
     * What the open deals are worth in roubles; a future counts by its open profit, not its price.
     *
     * @return numeric-string
     */
    public function getAssetsValue(Account $account): string
    {
        $value = '0';
        foreach ($this->dealRepository->findForAccount((int) $account->getId()) as $deal) {
            $dealData = new DealData($deal, $this->currencyService);
            $value = bcadd(
                $value,
                $dealData->getSecurityType() === SecurityTypeEnum::Future
                    ? $dealData->getProfitInBaseCurrency()
                    : $dealData->getFullCurrentPriceInBaseCurrency(),
                4,
            );
        }

        return $value;
    }

    public function getTotalBalance(Account $account): string
    {
        $total = bcadd($this->getAssetsValue($account), $account->getBalance(), 2);
        foreach ($account->getCashByCurrency() as $currency => $amount) {
            if ($currency !== 'RUB') {
                $total = bcadd($total, bcmul($amount, $this->currencyService->getCurrencyRate($currency), 2), 2);
            }
        }

        return $total;
    }
}
