<?php

declare(strict_types=1);

namespace App\Deposits\Domain;

use App\Deposits\Infrastructure\Persistence\Repository\DepositAccountRepository;
use App\Shared\Domain\CreatedByProvider;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\CreatedUserProviderInterface;
use App\Shared\Domain\UpdatedByProvider;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use App\Shared\Domain\UpdatedUserProviderInterface;
use App\Shared\Domain\User;
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
    private User $user;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct(
        string $name,
        User $user
    ) {
        $this->name = $name;
        $this->user = $user;
    }

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
