<?php

namespace App\DataFixtures;

use App\Entity\ExpensesCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExpensesCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->persist(
            (new ExpensesCategory())
                ->setUserId(1)
                ->setName('Shops and Others')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
        );
        $manager->persist(
            (new ExpensesCategory())
                ->setUserId(1)
                ->setName('Regular Payments')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
        );
        $manager->persist(
            (new ExpensesCategory())
                ->setUserId(1)
                ->setName('Base Expenses')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable())
        );
        $manager->flush();
    }
}
