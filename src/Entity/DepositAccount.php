<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DepositAccountRepository;
use App\Shared\CreatedByProvider;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\CreatedUserProviderInterface;
use App\Shared\UpdatedByProvider;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use App\Shared\UpdatedUserProviderInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepositAccountRepository::class)]
#[ORM\Table(name: 'deposit_accounts')]
class DepositAccount implements
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface,
    CreatedUserProviderInterface,
    UpdatedUserProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;
    use CreatedByProvider;
    use UpdatedByProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
