<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\AccountRepository;
use App\Response\DTO\Accounts\AccountListItemResponseDTO;
use App\Response\DTO\Accounts\AccountResponseDTO;

class AccountService
{
    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {
    }

    public function getSimpleListOfAccountsForUser(?User $user): array
    {
        $accounts = $this->accountRepository->findBy(['userId' => $user->getId()]);
        $result = [];
        foreach ($accounts as $account) {
            $result[] = new AccountResponseDTO($account->getId(), $account->getName());
        }
        return $result;
    }

    public function getAccountsListForUser(?User $user): array
    {
        $items = $this->accountRepository->findByUserIdWithDeposits($user->getId() ?? 0);
        $result = [];
        foreach ($items as $item) {
            $usdRate = 0; // TODO: Add the currency rate
            $account = $item['account'];

            $currentValue = round($account->getCurrentSumOfAssets() + $account->getBalance() + ($account->getUsdBalance() * $usdRate), 2);
            $sumDeposits = (float) $item['deposits_sum'] ?? 0;

            $result[] = new AccountListItemResponseDTO(
                id:           $account->getId(),
                name:         $account->getName(),
                balance:      $account->getBalance(),
                usdBalance:   $account->getUsdBalance(),
                deposits:     $sumDeposits,
                currentValue: $currentValue,
                fullProfit:   round($currentValue - $sumDeposits, 2),
            );
        }
        return $result;
    }
}
