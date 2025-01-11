<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Infrastructure\Persistence\Repository\InvestmentRepository;
use App\Shared\Domain\CreatedByProvider;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\CreatedUserProviderInterface;
use App\Shared\Domain\UpdatedByProvider;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use App\Shared\Domain\UpdatedUserProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvestmentRepository::class)]
#[ORM\Table(name: 'investments')]
class Investment implements
    CreatedUserProviderInterface,
    UpdatedUserProviderInterface,
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface
{
    use CreatedByProvider;
    use UpdatedByProvider;
    use CreatedDateProvider;
    use UpdatedDateProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id')]
    private int $userId;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2)]
    private string $sum;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'investments')]
    private ?Account $account;

    public function __construct(string $sum, \DateTimeImmutable $date, Account $account, int $userId)
    {
        $this->sum = $sum;
        $this->date = $date;
        $this->account = $account;
        $this->userId = $userId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }
}
