<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Listener;

use App\Shared\Domain\UpdatedUserProviderInterface;
use App\Shared\Domain\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsDoctrineListener(event: Events::preUpdate)]
final class UpdatedByListener
{
    public function __construct(
        private readonly TokenStorageInterface $token,
    ) {
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(LifecycleEventArgs $event): void
    {
        $target = $event->getObject();

        /** @var ?User $user */
        $user = $this->token->getToken()?->getUser();

        if ($target instanceof UpdatedUserProviderInterface && $user) {
            $target->wasUpdatedBy($user);
        }
    }
}
