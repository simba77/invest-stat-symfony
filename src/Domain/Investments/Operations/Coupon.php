<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations;

use App\Domain\Investments\Accounts\Account;
use App\Domain\Shared\User;
use App\Infrastructure\Persistence\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupons')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Account $account;

    #[ORM\Column(length: 255)]
    private string $ticker;

    #[ORM\Column(length: 255)]
    private string $stockMarket;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $amount;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $date;

    public function __construct(
        User $user,
        Account $account,
        string $ticker,
        string $stockMarket,
        string $amount,
        \DateTimeInterface $date
    ) {
        $this->user = $user;
        $this->account = $account;
        $this->ticker = $ticker;
        $this->stockMarket = $stockMarket;
        $this->amount = $amount;
        $this->date = $date;
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

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getTicker(): ?string
    {
        return $this->ticker;
    }

    public function setTicker(string $ticker): static
    {
        $this->ticker = $ticker;

        return $this;
    }

    public function getStockMarket(): ?string
    {
        return $this->stockMarket;
    }

    public function setStockMarket(string $stockMarket): static
    {
        $this->stockMarket = $stockMarket;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

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
