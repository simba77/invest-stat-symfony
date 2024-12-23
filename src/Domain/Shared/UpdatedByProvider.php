<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Doctrine\ORM\Mapping as ORM;

/**
 * @mixin UpdatedUserProviderInterface
 * @psalm-require-implements UpdatedUserProviderInterface
 */
trait UpdatedByProvider
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    #[ORM\Column(name: 'updated_by', type: 'integer', nullable: true)]
    private ?int $updatedBy;

    public function updatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function wasUpdatedBy(User $user): void
    {
        $this->updatedBy = $user->getId();
    }
}
