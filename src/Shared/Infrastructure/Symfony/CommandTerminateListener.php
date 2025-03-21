<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use App\Shared\Infrastructure\Metrics\AppMetrics;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ConsoleEvents::TERMINATE)]
class CommandTerminateListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AppMetrics $metrics,
    ) {
    }

    public function __invoke(ConsoleTerminateEvent $event): void
    {
        $this->logger->info('The {command} command was completed in {time} sec.', [
            'command' => $event->getCommand()?->getName() ?? 'unknown',
            'time'    => $this->metrics->getExecutionTime(),
        ]);
    }
}
