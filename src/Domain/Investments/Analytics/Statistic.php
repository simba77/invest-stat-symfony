<?php

declare(strict_types=1);

namespace App\Domain\Investments\Analytics;

use App\Domain\Investments\Accounts\Account;
use App\Infrastructure\Persistence\Repository\StatisticRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticRepository::class)]
class Statistic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $balance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $usdBalance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $investments = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $currentValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $profit = null;

    public function __construct(
        Account $account,
        \DateTimeInterface $date,
        string $balance,
        string $usdBalance,
        string $investments,
        string $currentValue,
        string $profit
    ) {
        $this->account = $account;
        $this->date = $date;
        $this->balance = $balance;
        $this->usdBalance = $usdBalance;
        $this->investments = $investments;
        $this->currentValue = $currentValue;
        $this->profit = $profit;
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(?string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getUsdBalance(): ?string
    {
        return $this->usdBalance;
    }

    public function setUsdBalance(?string $usdBalance): static
    {
        $this->usdBalance = $usdBalance;

        return $this;
    }

    public function getInvestments(): ?string
    {
        return $this->investments;
    }

    public function setInvestments(?string $investments): static
    {
        $this->investments = $investments;

        return $this;
    }

    public function getCurrentValue(): ?string
    {
        return $this->currentValue;
    }

    public function setCurrentValue(?string $currentValue): static
    {
        $this->currentValue = $currentValue;

        return $this;
    }

    public function getProfit(): ?string
    {
        return $this->profit;
    }

    public function setProfit(?string $profit): static
    {
        $this->profit = $profit;

        return $this;
    }
}
