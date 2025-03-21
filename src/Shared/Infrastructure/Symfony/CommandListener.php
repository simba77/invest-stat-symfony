<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use App\Shared\Infrastructure\Metrics\AppMetrics;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ConsoleEvents::COMMAND)]
class CommandListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AppMetrics $metrics,
    ) {
    }

    public function __invoke(ConsoleEvent $event): void
    {
        $this->logger->info('Start command: {command}', [
            'command' => $event->getCommand()?->getName() ?? 'unknown',
        ]);
    }
}
