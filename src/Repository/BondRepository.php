<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Bond;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bond>
 *
 * @method Bond|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bond|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bond[]    findAll()
 * @method Bond[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BondRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bond::class);
    }

//    /**
//     * @return Bond[] Returns an array of Bond objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Bond
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
