<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Account;
use App\Entity\Deal;
use App\Services\Deals\DealData;
use App\Services\MarketData\Currencies\CurrencyService;
use App\Services\MarketData\Securities\SecurityTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

class AccountCalculator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CurrencyService $currencyService
    ) {
    }

    public function recalculateBalanceForAllAccounts(): void
    {
        $accounts = $this->entityManager->getRepository(Account::class)->findAll();
        foreach ($accounts as $account) {
            $this->recalculateBalanceForAccount($account);
        }
    }

    public function recalculateBalanceForAccount(Account $account): void
    {
        $summaryData = $this->calculateSumOfAllDealsForAccount($account);
        $account->setStartSumOfAssets($summaryData['fullBuyPrice']);
        $account->setCurrentSumOfAssets($summaryData['fullCurrentPrice']);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
    }

    public function calculateSumOfAllDealsForAccount(Account $account): array
    {
        $deals = $this->entityManager->getRepository(Deal::class)->findForAccount($account);
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
