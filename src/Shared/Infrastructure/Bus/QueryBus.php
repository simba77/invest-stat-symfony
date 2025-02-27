<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\QueryBusInterface;
use RuntimeException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class QueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $messageBus
    ) {
    }

    #[\Override]
    public function ask(mixed $query): mixed
    {
        try {
            return $this->handle($query);
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof Throwable) {
                throw $previous;
            }
            throw new RuntimeException($e->getMessage());
        }
    }
}
