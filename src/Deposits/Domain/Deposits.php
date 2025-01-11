<?php

declare(strict_types=1);

namespace App\Deposits\Domain;

use App\Deposits\Application\Response\DTO\DepositAccountSummaryListItemDTO;
use App\Shared\Domain\User;

class Deposits
{
    public function __construct(
        private readonly DepositAccountRepositoryInterface $depositAccountRepository,
    ) {
    }

    /**
     * @return array<DepositAccountSummaryListItemDTO>
     */
    public function getDepositAccountsWithSummaryForUser(User $user): array
    {
        $accounts = $this->depositAccountRepository->getWithSummary($user);
        $result = [];
        foreach ($accounts as $account) {
            $result[] = new DepositAccountSummaryListItemDTO(
                id:     $account['id'],
                name:   $account['name'],
                total:  (float) $account['balance'],
                profit: (float) $account['profit'],
            );
        }
        return $result;
    }
}
