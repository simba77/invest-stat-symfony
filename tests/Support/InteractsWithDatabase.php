<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\User;
use App\Tests\Fixtures\UserFixtures;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Prepares and checks database state in kernel tests.
 *
 * Every test runs in a transaction that is rolled back afterwards (dama/doctrine-test-bundle),
 * so a test creates the data it needs and leaves nothing behind.
 *
 * @psalm-require-extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
trait InteractsWithDatabase
{
    protected function admin(): User
    {
        return $this->user(UserFixtures::ADMIN_EMAIL);
    }

    protected function otherUser(): User
    {
        return $this->user(UserFixtures::OTHER_USER_EMAIL);
    }

    protected function persist(object ...$entities): void
    {
        $entityManager = $this->entityManager();
        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
    }

    /**
     * Loads an entity from the database rather than from what the identity map remembers.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    protected function findFresh(string $class, ?int $id): ?object
    {
        $entityManager = $this->entityManager();
        $entityManager->clear();

        return $entityManager->find($class, $id);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $criteria
     * @return list<T>
     */
    protected function findFreshBy(string $class, array $criteria): array
    {
        $entityManager = $this->entityManager();
        $entityManager->clear();

        return array_values($entityManager->getRepository($class)->findBy($criteria, ['id' => 'ASC']));
    }

    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function user(string $email): User
    {
        $user = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, sprintf('Fixture user "%s" is missing, run "make test-db".', $email));

        return $user;
    }
}
