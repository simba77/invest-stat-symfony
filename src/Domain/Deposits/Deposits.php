<?php

declare(strict_types=1);

namespace App\Domain\Deposits;

use App\Application\Response\DTO\Deposits\DepositAccountEditFormDTO;
use App\Application\Response\DTO\Deposits\DepositAccountListItemDTO;
use App\Application\Response\DTO\Deposits\DepositAccountSummaryListItemDTO;
use App\Application\Response\DTO\Deposits\DepositEditFormDTO;
use App\Domain\Shared\User;

class Deposits
{
    public function __construct(
        private readonly DepositAccountRepositoryInterface $depositAccountRepository,
        private readonly DepositRepositoryInterface $depositRepository,
    ) {
    }

    /**
     * @return array<DepositAccountListItemDTO>
     */
    public function getDepositAccountsForUser(User $user): array
    {
        $accounts = $this->depositAccountRepository->getForUser($user);
        $result = [];
        foreach ($accounts as $account) {
            $result[] = new DepositAccountListItemDTO(
                id:   $account->getId(),
                name: $account->getName()
            );
        }
        return $result;
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

    public function getDepositAccountForUser(int $id, User $user): ?DepositAccountEditFormDTO
    {
        $account = $this->depositAccountRepository->getByIdAndUser($id, $user);
        return $account ? new DepositAccountEditFormDTO($account->getId(), $account->getName()) : null;
    }

    public function getDepositForUser(int $id, User $user): ?DepositEditFormDTO
    {
        $deposit = $this->depositRepository->getDepositByIdAndUser($id, $user);
        if ($deposit) {
            return new DepositEditFormDTO(
                $deposit->getId(),
                $deposit->getSum(),
                $deposit->getType(),
                $deposit->getDepositAccount()->getId(),
                $deposit->getDate()->format('Y-m-d')
            );
        }
        return null;
    }
}
