<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use App\Shared\Domain\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Only the accounts live in fixtures; every test creates the rest of its data itself.
 */
final class UserFixtures extends Fixture
{
    public const string ADMIN_EMAIL = 'admin@admin.com';
    public const string OTHER_USER_EMAIL = 'other@example.com';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $manager->persist($this->createUser(self::ADMIN_EMAIL, 'Admin Name'));
        $manager->persist($this->createUser(self::OTHER_USER_EMAIL, 'Other User'));
        $manager->flush();
    }

    private function createUser(string $email, string $name): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

        return $user;
    }
}
