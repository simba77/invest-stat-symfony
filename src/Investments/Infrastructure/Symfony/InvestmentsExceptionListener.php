<?php

declare(strict_types=1);

namespace App\Investments\Infrastructure\Symfony;

use App\Investments\Domain\Accounts\AccountCannotBeClosedException;
use App\Investments\Domain\Accounts\AccountCannotBeDeletedException;
use App\Investments\Domain\BrokerSync\AccountIsSyncedException;
use App\Investments\Domain\BrokerSync\BrokerSyncDisabledException;
use App\Investments\Domain\BrokerSync\Client\BrokerApiException;
use App\Investments\Domain\BrokerSync\Client\ExternalAccountNotFoundException;
use App\Investments\Domain\Journal\DealCannotBeDeletedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Turns refusals of the investments context into JSON errors whose message the SPA can show.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class InvestmentsExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        // Controllers that dispatch to the message bus directly get the handler's exception wrapped
        if ($exception instanceof HandlerFailedException && $exception->getPrevious() !== null) {
            $exception = $exception->getPrevious();
        }
        $status = match (true) {
            $exception instanceof BrokerApiException => JsonResponse::HTTP_BAD_GATEWAY,
            $exception instanceof ExternalAccountNotFoundException,
            $exception instanceof BrokerSyncDisabledException => JsonResponse::HTTP_BAD_REQUEST,
            $exception instanceof AccountIsSyncedException,
            $exception instanceof AccountCannotBeClosedException,
            $exception instanceof AccountCannotBeDeletedException,
            $exception instanceof DealCannotBeDeletedException => JsonResponse::HTTP_CONFLICT,
            default => null,
        };

        if ($status !== null) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], $status));
        }
    }
}
