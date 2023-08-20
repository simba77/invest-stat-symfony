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
        $accounts = $this->accountRepository->findByUserIdWithDeposits($user->getId() ?? 0);
        $result = [];
        foreach ($accounts as $account) {
            $result[] = new AccountListItemResponseDTO(
                id:           $account['account']->getId(),
                name:         $account['account']->getName(),
                balance:      $account['account']->getBalance() ?? 0,
                usdBalance:   $account['account']->getUsdBalance() ?? 0,
                deposits:     (float) $account['deposits_sum'] ?? 0,
                currentValue: 0,
                fullProfit:   0,
            );
        }
        return $result;
    }
}
