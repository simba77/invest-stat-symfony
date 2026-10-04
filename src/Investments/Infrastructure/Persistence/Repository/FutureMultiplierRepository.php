<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\FutureMultiplier;
use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @extends ServiceEntityRepository<FutureMultiplier>
 */
class FutureMultiplierRepository extends ServiceEntityRepository implements FutureMultiplierRepositoryInterface, ResetInterface
{
    /** @var array<string, FutureMultiplier>|null all multipliers by ticker, loaded on the first lookup */
    private ?array $byTicker = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FutureMultiplier::class);
    }

    public function save(FutureMultiplier $futureMultiplier): void
    {
        $em = $this->getEntityManager();
        $em->persist($futureMultiplier);
        $em->flush();
        $this->reset();
    }

    public function findById(int $id): ?FutureMultiplier
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Deal calculations ask for the multiplier of every futures deal, and there are only a few
     * multipliers: they are read once instead of one query per deal and price.
     */
    public function findByTicker(string $ticker): ?FutureMultiplier
    {
        if ($this->byTicker === null) {
            $this->byTicker = [];
            foreach ($this->findAll() as $multiplier) {
                $this->byTicker[$multiplier->getTicker()] = $multiplier;
            }
        }

        return $this->byTicker[$ticker] ?? null;
    }

    public function remove(FutureMultiplier $futureMultiplier): void
    {
        $em = $this->getEntityManager();
        $em->remove($futureMultiplier);
        $em->flush();
        $this->reset();
    }

    #[\Override]
    public function reset(): void
    {
        $this->byTicker = null;
    }
}
