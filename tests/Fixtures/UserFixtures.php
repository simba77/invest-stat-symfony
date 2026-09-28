<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Shared\Domain\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const string ADMIN = 'admin-user';
    public const string ADMIN_EMAIL = 'admin@admin.com';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setName('Admin Name');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));

        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::ADMIN, $admin);
    }
}
