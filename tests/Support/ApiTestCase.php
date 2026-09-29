<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Calls the JSON API through the real kernel, router, security and database.
 */
abstract class ApiTestCase extends WebTestCase
{
    use InteractsWithDatabase;

    protected KernelBrowser $client;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
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

    private function beforeRequest(): void
    {
        // Like a real request, the application starts with an empty identity map
        // and reads everything the test prepared from the database.
        $this->entityManager()->clear();
    }
}
