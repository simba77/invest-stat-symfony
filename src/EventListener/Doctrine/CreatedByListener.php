<?php

declare(strict_types=1);

namespace App\EventListener\Doctrine;

use App\Entity\User;
use App\Shared\CreatedUserProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsDoctrineListener(event: Events::prePersist)]
final class CreatedByListener
{
    public function __construct(
        private readonly TokenStorageInterface $token,
    ) {
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $target = $event->getObject();

        /** @var User $user */
        $user = $this->token->getToken()?->getUser();

        if ($target instanceof CreatedUserProviderInterface && $user) {
            $target->wasCreatedBy($user);
        }
    }
}
