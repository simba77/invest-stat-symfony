<?php

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

//    /**
//     * @return DepositAccount[] Returns an array of DepositAccount objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DepositAccount
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
