<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ManualOperation>
 */
class ManualOperationRepository extends ServiceEntityRepository implements ManualOperationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManualOperation::class);
    }

    #[\Override]
    public function findByAccount(Account $account): array
    {
        /** @var list<ManualOperation> */
        return $this->createQueryBuilder('o')
            ->select(['o', 'i'])
            ->leftJoin('o.instrument', 'i')
            ->andWhere('o.account = :account')
            ->setParameter('account', $account->getId())
            ->orderBy('o.executedAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function findOpening(Account $account, string $lot): ?ManualOperation
    {
        $id = self::openingId($lot);
        if ($id === null) {
            return null;
        }

        /** @var ManualOperation|null */
        return $this->createQueryBuilder('o')
            ->select(['o', 'i'])
            ->leftJoin('o.instrument', 'i')
            ->andWhere('o.id = :id')
            ->andWhere('o.account = :account')
            ->andWhere('o.type IN (:types)')
            ->setParameter('id', $id)
            ->setParameter('account', $account->getId())
            ->setParameter('types', [ManualOperationType::Buy->value, ManualOperationType::Short->value])
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[\Override]
    public function findAboutLot(Account $account, string $lot): array
    {
        $origin = explode('#', $lot, 2)[0];

        /** @var list<ManualOperation> */
        return $this->createQueryBuilder('o')
            ->andWhere('o.account = :account')
            ->andWhere('o.lot = :origin OR o.lot LIKE :parts')
            ->setParameter('account', $account->getId())
            ->setParameter('origin', $origin)
            ->setParameter('parts', $origin . '#%')
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function findByIdAndAccount(int $id, Account $account): ?ManualOperation
    {
        /** @var ManualOperation|null */
        return $this->createQueryBuilder('o')
            ->select(['o', 'i'])
            ->leftJoin('o.instrument', 'i')
            ->andWhere('o.id = :id')
            ->andWhere('o.account = :account')
            ->setParameter('id', $id)
            ->setParameter('account', $account->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[\Override]
    public function countByAccount(Account $account): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.account = :account')
            ->setParameter('account', $account->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function findPageByAccount(Account $account, int $offset, int $limit): array
    {
        /** @var list<ManualOperation> */
        return $this->createQueryBuilder('o')
            ->select(['o', 'i'])
            ->leftJoin('o.instrument', 'i')
            ->andWhere('o.account = :account')
            ->setParameter('account', $account->getId())
            ->orderBy('o.executedAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function findOpenings(Account $account, string ...$lots): array
    {
        $ids = [];
        foreach ($lots as $lot) {
            $id = self::openingId($lot);
            if ($id !== null) {
                $ids[$lot] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        /** @var list<ManualOperation> $openings */
        $openings = $this->createQueryBuilder('o')
            ->select(['o', 'i'])
            ->leftJoin('o.instrument', 'i')
            ->andWhere('o.id IN (:ids)')
            ->andWhere('o.account = :account')
            ->andWhere('o.type IN (:types)')
            ->setParameter('ids', array_values(array_unique($ids)))
            ->setParameter('account', $account->getId())
            ->setParameter('types', [ManualOperationType::Buy->value, ManualOperationType::Short->value])
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($openings as $opening) {
            $byId[$opening->getId()] = $opening;
        }
        $result = [];
        foreach ($ids as $lot => $id) {
            if (isset($byId[$id])) {
                $result[$lot] = $byId[$id];
            }
        }

        return $result;
    }

    #[\Override]
    public function save(ManualOperation ...$operations): void
    {
        $em = $this->getEntityManager();
        foreach ($operations as $operation) {
            $em->persist($operation);
        }
        $em->flush();
    }

    #[\Override]
    public function remove(ManualOperation ...$operations): void
    {
        $em = $this->getEntityManager();
        foreach ($operations as $operation) {
            $em->remove($operation);
        }
        $em->flush();
    }

    private static function openingId(string $lot): ?int
    {
        if (preg_match('/^' . preg_quote(ManualOperation::LOT_PREFIX, '/') . '(\d+)(#\d+)?$/', $lot, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
