<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\BrokerSync\BrokerAccountLink;
use App\Investments\Domain\BrokerSync\BrokerOperation;
use App\Investments\Domain\BrokerSync\BrokerOperationRepositoryInterface;
use App\Investments\Domain\BrokerSync\BrokerOperationState;
use App\Investments\Domain\BrokerSync\BrokerOperationType;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BrokerOperation>
 */
class BrokerOperationRepository extends ServiceEntityRepository implements BrokerOperationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BrokerOperation::class);
    }

    #[\Override]
    public function findByExternalIds(BrokerAccountLink $link, array $externalIds): array
    {
        if ($externalIds === []) {
            return [];
        }

        /** @var list<BrokerOperation> $operations */
        $operations = $this->createQueryBuilder('o')
            ->andWhere('o.link = :link')
            ->andWhere('o.externalId IN (:ids)')
            ->setParameter('link', $link->getId())
            ->setParameter('ids', $externalIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($operations as $operation) {
            $result[$operation->getExternalId()] = $operation;
        }

        return $result;
    }

    #[\Override]
    public function findExecuted(BrokerAccountLink $link): array
    {
        /** @var list<BrokerOperation> */
        return $this->createQueryBuilder('o')
            ->andWhere('o.link = :link')
            ->andWhere('o.state = :state')
            ->setParameter('link', $link->getId())
            ->setParameter('state', BrokerOperationState::Executed)
            ->orderBy('o.executedAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function findExecutedByTypes(BrokerAccountLink $link, array $types): array
    {
        if ($types === []) {
            return [];
        }

        /** @var list<BrokerOperation> */
        return $this->createQueryBuilder('o')
            ->andWhere('o.link = :link')
            ->andWhere('o.state = :state')
            ->andWhere('o.type IN (:types)')
            ->setParameter('link', $link->getId())
            ->setParameter('state', BrokerOperationState::Executed)
            ->setParameter('types', array_map(static fn (BrokerOperationType $type) => $type->value, $types))
            ->orderBy('o.executedAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function countByLink(BrokerAccountLink $link): int
    {
        return $this->count(['link' => $link]);
    }

    #[\Override]
    public function saveAll(array $operations): void
    {
        $em = $this->getEntityManager();
        foreach ($operations as $operation) {
            $em->persist($operation);
        }
        $em->flush();
    }

    #[\Override]
    public function removeByLink(BrokerAccountLink $link): void
    {
        $this->createQueryBuilder('o')
            ->delete()
            ->andWhere('o.link = :link')
            ->setParameter('link', $link->getId())
            ->getQuery()
            ->execute();
    }
}
