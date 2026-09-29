<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Bus;

use App\Shared\Infrastructure\Bus\SyncCommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class SyncCommandBusTest extends TestCase
{
    public function testHandlesCommandRightAway(): void
    {
        $command = new \stdClass();
        $handled = [];
        $bus = $this->bus(static function (object $received) use (&$handled): void {
            $handled[] = $received;
        });

        $bus->dispatch($command);

        self::assertSame([$command], $handled);
    }

    public function testRethrowsTheHandlerExceptionUnwrapped(): void
    {
        // Callers catch their own exceptions, not Messenger's HandlerFailedException
        $failure = new \DomainException('Account with id "5" not found');
        $bus = $this->bus(static fn() => throw $failure);

        try {
            $bus->dispatch(new \stdClass());
            self::fail('The handler exception was expected.');
        } catch (\DomainException $caught) {
            self::assertSame($failure, $caught);
        }
    }

    private function bus(callable $handler): SyncCommandBus
    {
        return new SyncCommandBus(new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([\stdClass::class => [$handler]])),
        ]));
    }
}
