<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\LazyServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T of object
 * @template-extends LazyServiceEntityRepository<T>
 *
 * @psalm-suppress InternalClass
 */
abstract class ServiceEntityRepository extends LazyServiceEntityRepository
{
    /**
     * @psalm-param class-string $entityClass
     *
     * @psalm-suppress InternalMethod
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }
}
