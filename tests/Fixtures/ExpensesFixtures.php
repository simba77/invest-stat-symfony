<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Expenses\Domain\ExpensesCategory;
use App\Shared\Domain\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ExpensesFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $admin = $this->getReference(UserFixtures::ADMIN, User::class);

        foreach (['Shops and Others', 'Regular Payments', 'Base Expenses'] as $name) {
            $manager->persist(new ExpensesCategory($name, $admin->getId()));
        }

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
