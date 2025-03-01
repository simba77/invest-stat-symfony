<?php

declare(strict_types=1);

namespace App\Deposits\Infrastructure\Persistence\Repository;

use App\Deposits\Domain\Deposit;
use App\Deposits\Domain\DepositRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository;
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

    #[\Override]
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

    #[\Override]
    public function getDepositsForUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['date' => Criteria::DESC]);
    }

    #[\Override]
    public function getDepositByIdAndUser(int $id, User $user): ?Deposit
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    #[\Override]
    public function save(Deposit $deposit): void
    {
        $em = $this->getEntityManager();
        $em->persist($deposit);
        $em->flush();
    }

    #[\Override]
    public function remove(Deposit $deposit): void
    {
        $em = $this->getEntityManager();
        $em->remove($deposit);
        $em->flush();
    }
}
