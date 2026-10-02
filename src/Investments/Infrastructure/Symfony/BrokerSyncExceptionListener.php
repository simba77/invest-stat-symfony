<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Symfony;

use App\Investments\Domain\BrokerSync\BrokerSyncDisabledException;
use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\ExternalAccountNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Turns broker sync failures into JSON errors whose message the SPA can show.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class BrokerSyncExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $status = match (true) {
            $exception instanceof BrokerApiException => JsonResponse::HTTP_BAD_GATEWAY,
            $exception instanceof ExternalAccountNotFoundException,
            $exception instanceof BrokerSyncDisabledException => JsonResponse::HTTP_BAD_REQUEST,
            default => null,
        };

        if ($status !== null) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], $status));
        }
    }
}
