<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Deposit;
use App\Entity\DepositAccount;
use App\Entity\User;
use App\Response\DTO\Deposits\DepositAccountListItemDTO;
use App\Response\DTO\Deposits\DepositListItemDTO;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class DepositsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

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
}
