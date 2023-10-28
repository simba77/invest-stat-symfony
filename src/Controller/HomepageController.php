<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Deposit;
use App\Entity\Investment;
use App\Entity\User;
use App\Services\AccountService;
use App\Services\DepositsService;
use App\Services\MarketData\Currencies\CurrencyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class HomepageController extends AbstractController
{
    public function __construct(
        private readonly CurrencyService $currencyService,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountService $accountService,
        private readonly DepositsService $depositsService,
    ) {
    }

    #[Route('/dashboard', name: 'app_homepage_index')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        $invested = (float) $this->entityManager->getRepository(Investment::class)->getSumByUserId($user->getId());
        $allAssetsSum = 0;
        $depositsSum = $this->entityManager->getRepository(Deposit::class)->getSumOfDepositsForUser($user);
        $depositAccounts = $this->depositsService->getDepositAccountsWithSummaryForUser($user);
        $depositAccounts = array_filter($depositAccounts, function ($item) {
            return $item->total > 0;
        });


        $accounts = $this->accountService->getAccountsListForUser($user);
        foreach ($accounts as $account) {
            $allAssetsSum += $account->currentValue;
        }

        $profit = $allAssetsSum - $invested;
        if ($invested > 0) {
            $profitPercent = round($profit / $invested * 100, 2);
        }

        return $this->json(
            [
                'usd'             => $this->currencyService->getUSDRUBRate(),
                'depositAccounts' => $depositAccounts,
                'summary'         => [
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
                        'total'    => $invested + $depositsSum,
                        'currency' => '₽',
                    ],

                    [
                        'name'     => 'All Assets',
                        'helpText' => 'The sum of all assets held by brokers',
                        'total'    => $allAssetsSum,
                        'currency' => '₽',
                    ],

                    [
                        'name'     => 'Profit',
                        'helpText' => 'Assets for The Current Day - The Invested Amount',
                        'percent'  => $profitPercent ?? 0,
                        'total'    => $profit,
                        'currency' => '₽',
                    ],

                    [
                        'name'     => 'Saving + All Brokers Assets',
                        'helpText' => 'Assets for The Current Day + Deposits',
                        'total'    => $allAssetsSum + $depositsSum,
                        'currency' => '₽',
                    ],
                ],
            ]
        );
    }
}
