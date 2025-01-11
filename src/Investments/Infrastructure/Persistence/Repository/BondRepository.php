<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\Bond;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bond>
 */
class BondRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bond::class);
    }
}
