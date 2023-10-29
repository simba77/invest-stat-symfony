<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StatisticRepository;
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
    private ?float $balance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?float $usdBalance = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?float $investments = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?float $currentValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?float $profit = null;

    public function __construct(
        Account $account,
        \DateTimeInterface $date,
        float $balance,
        float $usdBalance,
        float $investments,
        float $currentValue,
        float $profit
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

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(?float $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getUsdBalance(): ?float
    {
        return $this->usdBalance;
    }

    public function setUsdBalance(?float $usdBalance): static
    {
        $this->usdBalance = $usdBalance;

        return $this;
    }

    public function getInvestments(): ?float
    {
        return $this->investments;
    }

    public function setInvestments(?float $investments): static
    {
        $this->investments = $investments;

        return $this;
    }

    public function getCurrentValue(): ?float
    {
        return $this->currentValue;
    }

    public function setCurrentValue(?float $currentValue): static
    {
        $this->currentValue = $currentValue;

        return $this;
    }

    public function getProfit(): ?float
    {
        return $this->profit;
    }

    public function setProfit(?float $profit): static
    {
        $this->profit = $profit;

        return $this;
    }
}
