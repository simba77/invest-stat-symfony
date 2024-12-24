<?php

declare(strict_types=1);

namespace App\Domain\Deposits;

use App\Application\Response\DTO\Deposits\DepositAccountEditFormDTO;
use App\Application\Response\DTO\Deposits\DepositAccountListItemDTO;
use App\Application\Response\DTO\Deposits\DepositAccountSummaryListItemDTO;
use App\Application\Response\DTO\Deposits\DepositEditFormDTO;
use App\Application\Response\DTO\Deposits\DepositListItemDTO;
use App\Domain\Shared\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class Deposits
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<DepositListItemDTO>
     */
    public function getAllDepositsForUser(User $user): array
    {
        $deposits = $this->entityManager->getRepository(Deposit::class)->findBy(['user' => $user], ['date' => Criteria::DESC]);
        $result = [];
        foreach ($deposits as $deposit) {
            $result[] = new DepositListItemDTO(
                id:          $deposit->getId(),
                date:        $deposit->getDate()->format('d.m.Y'),
                sum:         $deposit->getSum(),
                typeName:    $deposit->getType() === 1 ? 'Deposit' : 'Percent',
                accountName: $deposit->getDepositAccount()->getName()
            );
        }
        return $result;
    }

    /**
     * @return array<DepositAccountListItemDTO>
     */
    public function getDepositAccountsForUser(User $user): array
    {
        $accounts = $this->entityManager->getRepository(DepositAccount::class)->findBy(['user' => $user]);
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
        $accounts = $this->entityManager->getRepository(DepositAccount::class)->getDepositAccountsWithSummary($user);
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
        $account = $this->entityManager->getRepository(DepositAccount::class)->findOneBy(['id' => $id, 'user' => $user]);
        return $account ? new DepositAccountEditFormDTO($account->getId(), $account->getName()) : null;
    }

    public function getDepositForUser(int $id, User $user): ?DepositEditFormDTO
    {
        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy(['id' => $id, 'user' => $user]);
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
