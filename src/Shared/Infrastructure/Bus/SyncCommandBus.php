<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\SyncCommandBusInterface;
use RuntimeException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class SyncCommandBus implements SyncCommandBusInterface
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * @throws Throwable
     */
    #[\Override]
    public function dispatch(object $command): void
    {
        try {
            $this->bus->dispatch($command);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof Throwable) {
                throw $previous;
            }
            throw new RuntimeException($e->getMessage());
        }
    }
}
