<?php

declare(strict_types=1);

namespace App\Expenses\Infrastructure\Persistence\Repository;

use App\Expenses\Domain\ExpensesCategory;
use App\Expenses\Domain\ExpensesCategoryRepositoryInterface;
use App\Shared\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExpensesCategory>
 */
class ExpensesCategoryRepository extends ServiceEntityRepository implements ExpensesCategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpensesCategory::class);
    }

    /**
     * @param User $user
     * @return ExpensesCategory[]
     */
    public function getCategoriesForUser(User $user): array
    {
        return $this->findBy(
            ['userId' => $user->getId()],
            ['id' => Order::Ascending->value]
        );
    }

    public function getByIdAndUser(int $id, User $user): ?ExpensesCategory
    {
        return $this->findOneBy(['id' => $id, 'userId' => $user->getId()]);
    }

    public function save(ExpensesCategory $expensesCategory): void
    {
        $em = $this->getEntityManager();
        $em->persist($expensesCategory);
        $em->flush();
    }

    public function remove(ExpensesCategory $expensesCategory): void
    {
        $em = $this->getEntityManager();
        $em->remove($expensesCategory);
        $em->flush();
    }
}
