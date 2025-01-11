<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Listener;

use App\Shared\Domain\CreatedDateProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Clock\ClockInterface;

/**
 * Each object that implements the {@see CreatedDateProviderInterface} interface will
 * force the creation date to be initialized using the system date returned
 * from the {@see ClockInterface} implementation of the interface before
 * SAVING data to the database.
 */
#[AsDoctrineListener(event: Events::prePersist)]
final class CreatedDateListener
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @param PrePersistEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $target = $event->getObject();

        if ($target instanceof CreatedDateProviderInterface) {
            $target->wasCreatedAt($this->clock->now());
        }
    }
}
