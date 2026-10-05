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
