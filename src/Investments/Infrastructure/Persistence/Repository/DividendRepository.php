<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dividend>
 */
class DividendRepository extends ServiceEntityRepository implements DividendRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dividend::class);
    }

    #[\Override]
    public function findAll(): array
    {
        return parent::findAll();
    }
}
