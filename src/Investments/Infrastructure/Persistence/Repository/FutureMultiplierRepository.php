<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\FutureMultiplier;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FutureMultiplier>
 */
class FutureMultiplierRepository extends ServiceEntityRepository implements FutureMultiplierRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FutureMultiplier::class);
    }

    public function save(FutureMultiplier $futureMultiplier): void
    {
        $em = $this->getEntityManager();
        $em->persist($futureMultiplier);
        $em->flush();
    }

    public function findById(int $id): ?FutureMultiplier
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function remove(FutureMultiplier $futureMultiplier): void
    {
        $em = $this->getEntityManager();
        $em->remove($futureMultiplier);
        $em->flush();
    }
}
