<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Response\DTO\Accounts\AccountDetailResponseDTO;
use App\Investments\Infrastructure\Persistence\Repository\AccountRepository;

class AccountService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
    ) {
    }

    /**
     * @param int $id
     * @param int $userId
     * @return AccountDetailResponseDTO|null
     */
    public function getAccountWithDetailInformation(int $id, int $userId): ?AccountDetailResponseDTO
    {
        $accountData = $this->accountRepository->findByUserAndIdWithDeposits($id, $userId);
        if (! $accountData) {
            return null;
        }

        $account = $accountData['account'];

        $totalBalance = $this->accountBalanceCalculator->getTotalBalance($account);
        $sumDeposits = $accountData['deposits_sum'] ?? '0';

        return new AccountDetailResponseDTO(
            id:           $account->getId(),
            name:         $account->getName(),
            balance:      $account->getBalance(),
            usdBalance:   $account->getUsdBalance(),
            deposits:     $sumDeposits,
            currentValue: $totalBalance,
            fullProfit:   bcsub($totalBalance, $sumDeposits, 2),
        );
    }
}
