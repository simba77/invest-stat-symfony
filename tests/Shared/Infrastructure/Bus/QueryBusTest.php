<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\QueryInterface;
use App\Shared\Infrastructure\Bus\QueryBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class QueryBusTest extends TestCase
{
    public function testReturnsWhatTheHandlerReturns(): void
    {
        $query = $this->query();
        $bus = $this->bus($query, static fn(): array => ['total' => '100.00']);

        self::assertSame(['total' => '100.00'], $bus->ask($query));
    }

    public function testRethrowsTheHandlerExceptionUnwrapped(): void
    {
        // Callers catch their own exceptions, not Messenger's HandlerFailedException
        $query = $this->query();
        $failure = new \DomainException('Account with id "5" not found');
        $bus = $this->bus($query, static fn() => throw $failure);

        try {
            $bus->ask($query);
            self::fail('The handler exception was expected.');
        } catch (\DomainException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    /**
     * @return QueryInterface<mixed>
     */
    private function query(): QueryInterface
    {
        return new class () implements QueryInterface {
        };
    }

    private function bus(QueryInterface $query, callable $handler): QueryBus
    {
        return new QueryBus(new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([$query::class => [$handler]])),
        ]));
    }
}
