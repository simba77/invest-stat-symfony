<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

use App\Investments\Application\Accounts\AccountBalanceCalculator;
use App\Investments\Application\Response\DTO\Accounts\AccountDetailResponseDTO;
use App\Investments\Application\Response\DTO\Accounts\AccountListItemResponseDTO;
use App\Investments\Application\Response\DTO\Accounts\AccountResponseDTO;
use App\Investments\Infrastructure\Persistence\Repository\AccountRepository;
use App\Shared\Domain\User;

class AccountService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly AccountBalanceCalculator $accountBalanceCalculator,
    ) {
    }

    /**
     * @return array<AccountResponseDTO>
     */
    public function getSimpleListOfAccountsForUser(?User $user): array
    {
        $accounts = $this->accountRepository->findBy(['userId' => $user->getId()]);
        $result = [];
        foreach ($accounts as $account) {
            $result[] = new AccountResponseDTO($account->getId(), $account->getName());
        }
        return $result;
    }

    /**
     * @return array<AccountListItemResponseDTO>
     */
    public function getAccountsListForUser(?User $user): array
    {
        $items = $this->accountRepository->findByUserIdWithDeposits($user);
        $result = [];
        foreach ($items as $item) {
            $account = $item['account'];

            $totalBalance = $this->accountBalanceCalculator->getTotalBalance($account);
            $sumDeposits = $item['deposits_sum'] ?? '0';

            $result[] = new AccountListItemResponseDTO(
                id:           $account->getId(),
                name:         $account->getName(),
                balance:      $account->getBalance(),
                usdBalance:   $account->getUsdBalance(),
                deposits:     $sumDeposits,
                currentValue: $totalBalance,
                fullProfit:   bcsub($totalBalance, $sumDeposits, 2),
            );
        }
        return $result;
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
