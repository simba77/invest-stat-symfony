<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Instruments\CurrencyRate;
use App\Investments\Domain\Instruments\CurrencyRateRepositoryInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CurrencyRate>
 */
class CurrencyRateRepository extends ServiceEntityRepository implements CurrencyRateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrencyRate::class);
    }

    #[\Override]
    public function findLatest(string $baseCurrency, string $targetCurrency): ?CurrencyRate
    {
        return $this->findOneBy(['baseCurrency' => $baseCurrency, 'targetCurrency' => $targetCurrency], ['date' => 'DESC']);
    }

    #[\Override]
    public function findOnDate(string $baseCurrency, string $targetCurrency, \DateTimeImmutable $date): ?CurrencyRate
    {
        return $this->findOneBy(['baseCurrency' => $baseCurrency, 'targetCurrency' => $targetCurrency, 'date' => $date->setTime(0, 0)]);
    }

    /**
     * Read as plain rows: the history is long, and the entity manager does not need to track it.
     */
    #[\Override]
    public function findHistory(string $baseCurrency, string $targetCurrency): array
    {
        /** @var list<array{date: \DateTimeImmutable, rate: numeric-string}> */
        return $this->createQueryBuilder('r')
            ->select('r.date', 'r.rate')
            ->andWhere('r.baseCurrency = :base')
            ->andWhere('r.targetCurrency = :target')
            ->setParameter('base', $baseCurrency)
            ->setParameter('target', $targetCurrency)
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    #[\Override]
    public function save(CurrencyRate ...$rates): void
    {
        $em = $this->getEntityManager();
        foreach ($rates as $rate) {
            $em->persist($rate);
        }
        $em->flush();
    }
}
