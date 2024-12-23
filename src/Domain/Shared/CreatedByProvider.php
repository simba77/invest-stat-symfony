<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Doctrine\ORM\Mapping as ORM;

/**
 * @mixin CreatedUserProviderInterface
 * @psalm-require-implements CreatedUserProviderInterface
 */
trait CreatedByProvider
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    #[ORM\Column(name: 'created_by', type: 'integer', nullable: true)]
    private ?int $createdBy;

    public function createdBy(): ?int
    {
        return $this->createdBy;
    }

    public function wasCreatedBy(User $user): void
    {
        $this->createdBy = $user->getId();
    }
}
