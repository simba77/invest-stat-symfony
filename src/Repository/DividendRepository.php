<?php

namespace App\Repository;

use App\Entity\Dividend;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dividend>
 *
 * @method Dividend|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dividend|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dividend[]    findAll()
 * @method Dividend[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DividendRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dividend::class);
    }
}
