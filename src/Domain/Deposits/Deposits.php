<?php

declare(strict_types=1);

namespace App\Domain\Deposits;

use App\Application\Response\DTO\Deposits\DepositAccountSummaryListItemDTO;
use App\Domain\Shared\User;

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
