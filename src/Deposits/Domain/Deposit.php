<?php

declare(strict_types=1);

namespace App\Deposits\Domain;

use App\Deposits\Infrastructure\Persistence\Repository\DepositRepository;
use App\Shared\Domain\CreatedByProvider;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\CreatedUserProviderInterface;
use App\Shared\Domain\UpdatedByProvider;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use App\Shared\Domain\UpdatedUserProviderInterface;
use App\Shared\Domain\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepositRepository::class)]
#[ORM\Table(name: 'deposits')]
class Deposit implements
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

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2)]
    private string $sum;

    #[ORM\Column]
    private int $type;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private DepositAccount $depositAccount;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date;

    public function __construct(
        string $sum,
        int $type,
        User $user,
        DepositAccount $depositAccount,
        \DateTimeInterface $date
    ) {
        $this->sum = $sum;
        $this->type = $type;
        $this->user = $user;
        $this->depositAccount = $depositAccount;
        $this->date = $date;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSum(): ?string
    {
        return $this->sum;
    }

    public function setSum(string $sum): static
    {
        $this->sum = $sum;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): static
    {
        $this->type = $type;

        return $this;
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

    public function getDepositAccount(): ?DepositAccount
    {
        return $this->depositAccount;
    }

    public function setDepositAccount(?DepositAccount $depositAccount): static
    {
        $this->depositAccount = $depositAccount;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }
}
