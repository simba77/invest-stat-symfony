<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\AccountRepository;
use App\Response\DTO\Accounts\AccountResponseDTO;

class AccountService
{
    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {
    }

    public function getAccountsForUser(?User $user): array
    {
        $accounts = $this->accountRepository->findBy(['userId' => $user->getId()]);
        $result = [];
        foreach ($accounts as $account) {
            $result[] = new AccountResponseDTO($account->getId(), $account->getName());
        }
        return $result;
    }
}
