<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coupon>
 */
class CouponRepository extends ServiceEntityRepository implements CouponRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coupon::class);
    }

    #[\Override]
    public function findAll(): array
    {
        return parent::findAll();
    }

    #[\Override]
    public function findByUser(?User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.account', 'a')
            ->andWhere('IDENTITY(a.user) = :user')
            ->setParameter('user', $user?->getId())
            ->orderBy('c.date', Order::Descending->value)
            ->addOrderBy('c.id', Order::Descending->value)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Coupon>
     */
    #[\Override]
    public function getPageByUserId(int $userId, int $offset, int $limit): array
    {
        return $this->createQueryBuilder('c')
            ->select(['c', 'a'])
            ->join('c.account', 'a')
            ->andWhere('IDENTITY(a.user) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.date', Order::Descending->value)
            ->addOrderBy('c.id', Order::Descending->value)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    #[\Override]
    public function countByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->join('c.account', 'a')
            ->andWhere('IDENTITY(a.user) = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function findByIdAndUser(int $id, User $user): ?Coupon
    {
        /** @var Coupon|null */
        return $this->createQueryBuilder('c')
            ->join('c.account', 'a')
            ->andWhere('c.id = :id')
            ->andWhere('IDENTITY(a.user) = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    #[\Override]
    public function findById(int $id): ?Coupon
    {
        return $this->findOneBy(['id' => $id]);
    }

    #[\Override]
    public function save(Coupon $coupon): void
    {
        $em = $this->getEntityManager();
        $em->persist($coupon);
        $em->flush();
    }

    #[\Override]
    public function remove(Coupon $coupon): void
    {
        $em = $this->getEntityManager();
        $em->remove($coupon);
        $em->flush();
    }

    #[\Override]
    public function sumByAccount(Account $account): string
    {
        $sum = $this->createQueryBuilder('c')
            ->select('SUM(c.amount)')
            ->andWhere('c.account = :account')
            ->setParameter('account', $account->getId())
            ->getQuery()
            ->getSingleScalarResult();

        /** @var numeric-string */
        return is_numeric($sum) ? (string) $sum : '0';
    }

    /**
     * @return list<Coupon>
     */
    #[\Override]
    public function findByAccount(Account $account): array
    {
        /** @var list<Coupon> */
        return $this->findBy(['account' => $account], ['id' => 'ASC']);
    }
}
