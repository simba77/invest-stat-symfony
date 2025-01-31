<?php

declare(strict_types=1);

namespace App\Investments\Application\Accounts;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Infrastructure\Persistence\Repository\DealRepository;

class AccountBalanceCalculator
{
    public function __construct(
        protected readonly AccountRepositoryInterface $accountRepository,
        protected readonly DealRepository $dealRepository,
        protected readonly CurrencyService $currencyService
    ) {
    }

    public function recalculateBalanceForAllAccounts(): void
    {
        $accounts = $this->accountRepository->findAll();
        foreach ($accounts as $account) {
            $this->recalculateBalance($account);
        }
    }

    public function recalculateBalance(Account $account): void
    {
        $summaryData = $this->calculateSumOfAllDealsForAccount($account);
        $account->setStartSumOfAssets($summaryData['fullBuyPrice']);
        $account->setCurrentSumOfAssets($summaryData['fullCurrentPrice']);
        $this->accountRepository->save($account);
    }

    /**
     * @param Account $account
     * @return array{fullBuyPrice: string, fullCurrentPrice: string}
     */
    public function calculateSumOfAllDealsForAccount(Account $account): array
    {
        $deals = $this->dealRepository->findForAccount($account);
        $fullBuyPrice = '0';
        $fullCurrentPrice = '0';
        foreach ($deals as $deal) {
            $dealData = new DealData($deal, $account, $this->currencyService);
            if ($dealData->getSecurityType() === SecurityTypeEnum::Future) {
                $fullCurrentPrice = bcadd($fullCurrentPrice, $dealData->getProfitInBaseCurrency(), 4);
            } else {
                $fullBuyPrice = bcadd($fullBuyPrice, $dealData->getFullBuyPriceInBaseCurrency(), 4);
                $fullCurrentPrice = bcadd($fullCurrentPrice, $dealData->getFullCurrentPriceInBaseCurrency(), 4);
            }
        }
        return [
            'fullBuyPrice'     => $fullBuyPrice,
            'fullCurrentPrice' => $fullCurrentPrice,
        ];
    }

    public function getTotalBalance(Account $account): string
    {
        $usdRate = $this->currencyService->getUSDRUBRate();
        $currentValue = bcadd($account->getCurrentSumOfAssets(), $account->getBalance(), 2);
        $usdBalance = bcmul($account->getUsdBalance(), $usdRate, 2);
        return bcadd($currentValue, $usdBalance, 2);
    }
}
