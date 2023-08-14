<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

// #[AsEventListener(event: 'kernel.response', method: 'onKernelResponse')]
class ValidationExceptionListener
{
    public function onKernelResponse(ResponseEvent $responseEvent)
    {
        if ($responseEvent->getResponse()->getStatusCode() === 422) {
            //$responseEvent->getResponse()->setContent((new JsonResponse(['test' => true]))->getContent());


            dd(json_decode($responseEvent->getResponse()->getContent(), true));
        }
    }
}
