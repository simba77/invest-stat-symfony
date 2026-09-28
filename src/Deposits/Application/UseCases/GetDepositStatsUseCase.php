<?php

declare(strict_types=1);

namespace App\Deposits\Application\UseCases;

use App\Deposits\Application\Response\DTO\DepositAccountStatsDTO;
use App\Deposits\Application\Response\DTO\DepositMonthlyStatsDTO;
use App\Deposits\Application\Response\DTO\DepositStatsDTO;
use App\Deposits\Application\Response\DTO\DepositStatsSummaryDTO;
use App\Deposits\Domain\DepositAccountRepositoryInterface;
use App\Deposits\Domain\DepositRepositoryInterface;
use App\Deposits\Domain\Stats\ActiveDaysCalculator;
use App\Deposits\Domain\Stats\ProfitabilityCalculator;
use App\Shared\Domain\User;
use Psr\Clock\ClockInterface;

final readonly class GetDepositStatsUseCase
{
    public function __construct(
        private DepositRepositoryInterface $depositRepository,
        private DepositAccountRepositoryInterface $depositAccountRepository,
        private ActiveDaysCalculator $activeDaysCalculator,
        private ProfitabilityCalculator $profitabilityCalculator,
        private ClockInterface $clock,
    ) {
    }

    public function execute(User $user): DepositStatsDTO
    {
        $userId = (int) $user->getId();
        $today = $this->clock->now()->setTime(0, 0);
        $transactionsByAccount = $this->depositRepository->getTransactionsByAccount($userId);

        $accounts = [];
        $totalBalance = '0';
        $totalProfit = '0';
        $totalGrossInvested = '0';
        $activeDaysPerAccount = [];

        foreach ($this->depositAccountRepository->getAccountStats($user) as $account) {
            $activeDays = $this->activeDaysCalculator->calculate($transactionsByAccount[$account['id']] ?? [], $today);
            $profitability = $this->profitabilityCalculator->forAccount($account['profit'], $account['gross_invested'], $activeDays);

            $accounts[] = new DepositAccountStatsDTO(
                id:                $account['id'],
                name:              $account['name'],
                balance:           $account['balance'],
                profit:            $account['profit'],
                grossInvested:     $account['gross_invested'],
                profitPercent:     $profitability->profitPercent,
                annualizedPercent: $profitability->annualizedPercent,
            );

            $totalBalance = bcadd($totalBalance, $account['balance'], 2);
            $totalProfit = bcadd($totalProfit, $account['profit'], 2);
            $totalGrossInvested = bcadd($totalGrossInvested, $account['gross_invested'], 2);
            $activeDaysPerAccount[] = $activeDays;
        }

        $summaryProfitability = $this->profitabilityCalculator->forPortfolio($totalProfit, $totalGrossInvested, $activeDaysPerAccount);

        return new DepositStatsDTO(
            summary:      new DepositStatsSummaryDTO(
                balance:           $totalBalance,
                profit:            $totalProfit,
                profitPercent:     $summaryProfitability->profitPercent,
                annualizedPercent: $summaryProfitability->annualizedPercent,
            ),
            monthlyStats: array_map(
                static fn(array $month): DepositMonthlyStatsDTO => new DepositMonthlyStatsDTO(
                    month:    $month['month'],
                    deposits: $month['deposits'],
                    profit:   $month['profit'],
                ),
                array_values($this->depositRepository->getMonthlyStats($userId)),
            ),
            accounts:     $accounts,
        );
    }
}
