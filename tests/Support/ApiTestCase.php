<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\User;
use App\Tests\Fixtures\UserFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Calls the JSON API through the real kernel, router, security and database.
 *
 * Every test runs in a transaction that is rolled back afterwards (dama/doctrine-test-bundle),
 * so a test creates the data it needs and leaves nothing behind.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function admin(): User
    {
        return $this->user(UserFixtures::ADMIN_EMAIL);
    }

    protected function otherUser(): User
    {
        return $this->user(UserFixtures::OTHER_USER_EMAIL);
    }

    protected function loginAs(User $user): void
    {
        $this->client->loginUser($user);
    }

    protected function getJson(string $uri): void
    {
        $this->beforeRequest();
        // Like the SPA (axios): a GET carries neither a body nor a Content-Type
        $this->client->request('GET', $uri, server: ['HTTP_ACCEPT' => 'application/json']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function postJson(string $uri, array $payload = []): void
    {
        $this->beforeRequest();
        $this->client->jsonRequest('POST', $uri, $payload);
    }

    protected function responseJson(): mixed
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Asserts a validation failure in the shape the frontend forms read: `violations[].propertyPath`.
     *
     * @param list<string> $fields
     */
    protected function assertViolatedFields(array $fields): void
    {
        self::assertResponseStatusCodeSame(422);

        /** @var array{violations: list<array{propertyPath: string}>} $body */
        $body = $this->responseJson();
        $violated = array_values(array_unique(array_column($body['violations'], 'propertyPath')));

        self::assertEqualsCanonicalizing($fields, $violated);
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

    private function beforeRequest(): void
    {
        // Like a real request, the application starts with an empty identity map
        // and reads everything the test prepared from the database.
        $this->entityManager()->clear();
    }
}
