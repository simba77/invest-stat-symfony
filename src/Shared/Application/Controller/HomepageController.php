<?php

declare(strict_types=1);

namespace App\Shared\Application\Controller;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\Deposits;
use App\Investments\Application\Response\Compiler\AccountsListCompiler;
use App\Investments\Application\Response\Compiler\AnnualStatisticCompiler;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Analytics\StatisticRepositoryInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyService;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealData;
use App\Investments\Domain\Operations\Investment;
use App\Shared\Domain\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]
class HomepageController extends AbstractController
{
    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly EntityManagerInterface $entityManager,
        private readonly Deposits $depositsService,
        protected readonly AccountRepositoryInterface $accountRepository,
        protected readonly AccountsListCompiler $accountsListCompiler,
        public readonly AnnualStatisticCompiler $annualStatisticCompiler,
        public readonly StatisticRepositoryInterface $statisticRepository,
        public readonly DealRepositoryInterface $dealRepository,
        private readonly FutureMultiplierRepositoryInterface $futureMultiplierRepository
    ) {
    }

    #[Route('/dashboard', name: 'app_homepage_index')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        $invested = $this->entityManager->getRepository(Investment::class)->getSumByUserId($user->getId());
        $allAssetsSum = '0';
        $depositsSum = $this->entityManager->getRepository(Deposit::class)->getSumOfDepositsForUser($user);
        $depositAccounts = $this->depositsService->getDepositAccountsWithSummaryForUser($user);
        $depositAccounts = array_filter($depositAccounts, function ($item) {
            return $item->total > 0;
        });

        $dailyChange = '0';
        $allActiveDeals = $this->dealRepository->findByUserId($user->getId());
        foreach ($allActiveDeals as $deal) {
            $dealData = new DealData($deal, $this->currencyService, $this->futureMultiplierRepository);
            $dailyChange = bcadd($dailyChange, $dealData->getFullDailyProfitInBaseCurrency(), 2);
        }

        $accounts = $this->accountRepository->findByUserWithDeposits($user);
        $accountsList = $this->accountsListCompiler->compile($accounts);
        foreach ($accountsList as $account) {
            $allAssetsSum = bcadd($account->currentValue, $allAssetsSum, 2);
        }

        $profit = bcsub($allAssetsSum, $invested, 2);
        if ($invested > 0) {
            $profitPercent = bcmul(bcdiv($profit, $invested, 5), '100', 2);
        }

        return $this->json(
            [
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
                ],
                'statisticByYears' => $this->annualStatisticCompiler->compile(
                    [
                        'yearsData'  => $this->statisticRepository->getStatisticByYears(),
                        'latestData' => $this->statisticRepository->getLatestStatistic(),
                    ]
                ),
            ]
        );
    }
}
