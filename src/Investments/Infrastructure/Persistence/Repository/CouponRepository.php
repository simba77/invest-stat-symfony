<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Persistence\Repository;

use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Shared\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findByUser(?User $user): array
    {
        return $this->findBy(['user' => $user], ['date' => Order::Descending->value, 'id' => Order::Descending->value]);
    }

    public function findByIdAndUser(int $id, User $user): ?Coupon
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function save(Coupon $coupon): void
    {
        $em = $this->getEntityManager();
        $em->persist($coupon);
        $em->flush();
    }

    public function remove(Coupon $coupon): void
    {
        $em = $this->getEntityManager();
        $em->remove($coupon);
        $em->flush();
    }
}
