<?php

declare(strict_types=1);

namespace App\Shared\Application\UseCases\Homepage;

use App\Deposits\Domain\DepositRepositoryInterface;
use App\Deposits\Domain\Deposits;
use App\Investments\Application\Response\Compiler\AccountsListCompiler;
use App\Investments\Application\Response\Compiler\AnnualStatisticCompiler;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Analytics\StatisticRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use App\Shared\Domain\User;

final readonly class GetHomepageDataUseCase
{
    public function __construct(
        private CurrencyService $currencyService,
        private InvestmentRepositoryInterface $investmentRepository,
        private DepositRepositoryInterface $depositRepository,
        private Deposits $depositsService,
        private AccountRepositoryInterface $accountRepository,
        private AccountsListCompiler $accountsListCompiler,
        private AnnualStatisticCompiler $annualStatisticCompiler,
        private StatisticRepositoryInterface $statisticRepository,
        private DealRepositoryInterface $dealRepository,
        private FutureMultiplierRepositoryInterface $futureMultiplierRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $invested = $this->investmentRepository->getSumByUserId($user->getId());
        $allAssetsSum = '0';
        $depositsSum = $this->depositRepository->getSumOfDepositsForUserId($user->getId());
        $depositAccounts = $this->getNotEmptyDepositAccounts($user);

        $blockedAssetsSum = '0';
        $dailyChange = '0';
        $allActiveDeals = $this->dealRepository->findByUserId($user->getId());
        foreach ($allActiveDeals as $deal) {
            $dealData = new DealData($deal, $this->currencyService, $this->futureMultiplierRepository);
            $dailyChange = bcadd($dailyChange, $dealData->getFullDailyProfitInBaseCurrency(), 2);

            if ($dealData->getStatus() === DealStatus::Blocked) {
                $blockedAssetsSum = bcadd($blockedAssetsSum, $dealData->getFullCurrentPriceInBaseCurrency());
            }
        }

        $accounts = $this->accountRepository->findByUserWithDeposits($user->getId());
        $accountsList = $this->accountsListCompiler->compile($accounts);
        foreach ($accountsList as $account) {
            $allAssetsSum = bcadd($account->currentValue, $allAssetsSum, 2);

            if ($account->id === 1) {
                $blockedAssetsSum = bcadd($blockedAssetsSum, bcmul($account->usdBalance, $this->currencyService->getUSDRUBRate()), 2);
            }
        }

        $profit = bcsub($allAssetsSum, $invested, 2);
        if ($invested > 0) {
            $profitPercent = bcmul(bcdiv($profit, $invested, 5), '100', 2);
        }

        return [
            'usd'              => $this->currencyService->getUSDRUBRate(),
            'depositAccounts'  => $depositAccounts,
            'summary'          => [
                [
                    'name'     => 'The Invested Amount',
                    'total'    => $invested,
                    'currency' => '₽',
                ],
                [
                    'name'     => 'Deposits',
                    'total'    => $depositsSum,
                    'currency' => '₽',
                ],
                [
                    'name'     => 'Deposits + Investments',
                    'helpText' => 'Deposits + Investments',
                    'total'    => bcadd($invested, $depositsSum, 2),
                    'currency' => '₽',
                ],
                [
                    'name'        => 'All Assets',
                    'helpText'    => 'The sum of all assets held by brokers',
                    'dailyChange' => $dailyChange,
                    'percent'     => bcmul(bcdiv($dailyChange, $allAssetsSum, 5), '100', 2),
                    'total'       => $allAssetsSum,
                    'currency'    => '₽',
                ],
                [
                    'name'     => 'Profit',
                    'helpText' => 'Assets for The Current Day - The Invested Amount',
                    'percent'  => $profitPercent ?? '0',
                    'total'    => $profit,
                    'currency' => '₽',
                ],
                [
                    'name'     => 'Saving + All Brokers Assets',
                    'helpText' => 'Assets for The Current Day + Deposits',
                    'total'    => bcadd($allAssetsSum, $depositsSum, 2),
                    'currency' => '₽',
                ],
                [
                    'name'     => 'Blocked Assets',
                    'total'    => $blockedAssetsSum,
                    'currency' => '₽',
                ],
                [
                    'name'     => 'Liquid assets',
                    'total'    => bcsub(bcadd($allAssetsSum, $depositsSum, 2), $blockedAssetsSum, 2),
                    'currency' => '₽',
                ],
            ],
            'statisticByYears' => $this->annualStatisticCompiler->compile(
                [
                    'yearsData'  => $this->statisticRepository->getStatisticByYears(),
                    'latestData' => $this->statisticRepository->getLatestStatistic(),
                ]
            ),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function getNotEmptyDepositAccounts(User $user): array
    {
        $depositAccounts = $this->depositsService->getDepositAccountsWithSummaryForUser($user);

        return array_values(array_filter($depositAccounts, static function ($item) {
            return $item->total > 0;
        }));
    }
}
