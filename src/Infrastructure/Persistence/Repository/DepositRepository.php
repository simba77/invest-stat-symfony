<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Deposits\Deposit;
use App\Domain\Deposits\DepositRepositoryInterface;
use App\Domain\Shared\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deposit>
 */
class DepositRepository extends ServiceEntityRepository implements DepositRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    public function getSumOfDepositsForUser(User $user): string
    {
        $data = $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->select("SUM(d.sum) as sum_of_deposits")
            ->getQuery()
            ->getOneOrNullResult();

        return (string) $data['sum_of_deposits'];
    }

    public function getDepositsForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['date' => Criteria::DESC]);
    }

    public function getDepositByIdAndUser(int $id, User $user): ?Deposit
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    public function save(Deposit $deposit): void
    {
        $em = $this->getEntityManager();
        $em->persist($deposit);
        $em->flush();
    }

    public function remove(Deposit $deposit): void
    {
        $em = $this->getEntityManager();
        $em->remove($deposit);
        $em->flush();
    }
}
