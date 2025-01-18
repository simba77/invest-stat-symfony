<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\SyncCommandBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class SyncCommandBus implements SyncCommandBusInterface
{
    public function __construct(
        public readonly MessageBusInterface $bus
    ) {
    }

    /**
     * @throws Throwable
     */
    public function dispatch(object $command): void
    {
        try {
            $this->bus->dispatch($command);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }
}
