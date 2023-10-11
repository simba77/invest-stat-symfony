<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\Repository\DealRepository;
use App\Services\Deals\DealData;
use Doctrine\ORM\EntityManagerInterface;

class AccountCalculator
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly DealRepository $dealRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function recalculateBalanceForAllAccounts(): void
    {
        $accounts = $this->accountRepository->findAll();
        foreach ($accounts as $account) {
            $summaryData = $this->calculateSumOfAllDealsForAccount($account);
            $account->setStartSumOfAssets($summaryData['fullBuyPrice']);
            $account->setCurrentSumOfAssets($summaryData['fullCurrentPrice']);
            $this->entityManager->persist($account);
            $this->entityManager->flush();
        }
    }

    private function calculateSumOfAllDealsForAccount(Account $account): array
    {
        $deals = $this->dealRepository->findForAccount($account);
        $fullBuyPrice = 0;
        $fullCurrentPrice = 0;
        foreach ($deals as $deal) {
            $dealData = new DealData($deal, $account);
            $fullBuyPrice += $dealData->getFullBuyPrice();
            $fullCurrentPrice += $dealData->getFullCurrentPrice();
        }
        return [
            'fullBuyPrice'     => $fullBuyPrice,
            'fullCurrentPrice' => $fullCurrentPrice,
        ];
    }
}
