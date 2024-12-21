<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $event->getPassport()->addBadge(new RememberMeBadge());

        $request = $event->getRequest();
        $data = json_decode($request->getContent());
        $request->attributes->set('_remember_me', $data->remember_me ?? '');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
        ];
    }
}
