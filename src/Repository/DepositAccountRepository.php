<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DepositAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DepositAccount>
 *
 * @method DepositAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method DepositAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method DepositAccount[]    findAll()
 * @method DepositAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepositAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepositAccount::class);
    }
}
