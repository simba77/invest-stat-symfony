<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class NotFoundExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotFoundException) {
            $request = $event->getRequest();
            if ($request->getContentTypeFormat() === 'json') {
                $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], 404));
            } else {
                $event->setResponse(new Response($exception->getMessage(), 404));
            }
        }
    }
}
