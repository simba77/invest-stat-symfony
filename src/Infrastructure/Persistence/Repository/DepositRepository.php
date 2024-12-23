<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Deposits\Deposit;
use App\Domain\Shared\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deposit>
 */
class DepositRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    public function getSumOfDepositsForUser(User $user): string
    {
        $data = $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->select("SUM(d.sum) as sum_of_deposits")
            ->getQuery()
            ->getOneOrNullResult();

        return (string) $data['sum_of_deposits'];
    }
}
