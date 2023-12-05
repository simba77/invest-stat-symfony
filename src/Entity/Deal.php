<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DealRepository;
use App\Services\Deals\DealStatus;
use App\Services\Deals\DealType;
use App\Shared\CreatedByProvider;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\CreatedUserProviderInterface;
use App\Shared\UpdatedByProvider;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use App\Shared\UpdatedUserProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\Table(name: 'deals')]
class Deal implements
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

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    #[ORM\Column(length: 255)]
    private ?string $ticker = null;

    #[ORM\Column(length: 255)]
    private ?string $stockMarket = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?float $buyPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $targetPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $sellPrice = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: DealStatus::class)]
    private ?DealStatus $status = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: DealType::class)]
    private ?DealType $type = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closingDate = null;

    public function __construct(
        User       $user,
        Account    $account,
        string     $ticker,
        string     $stockMarket,
        DealStatus $status,
        DealType   $type,
        int        $quantity,
        float      $buyPrice,
        float      $targetPrice = 0,
        float      $sellPrice = 0,
    )
    {
        $this->user = $user;
        $this->account = $account;
        $this->ticker = $ticker;
        $this->stockMarket = $stockMarket;
        $this->status = $status;
        $this->type = $type;
        $this->quantity = $quantity;
        $this->buyPrice = $buyPrice;
        $this->targetPrice = $targetPrice;
        $this->sellPrice = $sellPrice;
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

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getBuyPrice(): ?float
    {
        return $this->buyPrice;
    }

    public function setBuyPrice(float $buyPrice): static
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }

    public function getTargetPrice(): ?float
    {
        return $this->targetPrice;
    }

    public function setTargetPrice(?float $targetPrice): static
    {
        $this->targetPrice = $targetPrice;

        return $this;
    }

    public function getSellPrice(): ?float
    {
        return $this->sellPrice;
    }

    public function setSellPrice(?float $sellPrice): static
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getStatus(): ?DealStatus
    {
        return $this->status;
    }

    public function setStatus(DealStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): ?DealType
    {
        return $this->type;
    }

    public function setType(DealType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getClosingDate(): ?\DateTimeInterface
    {
        return $this->closingDate;
    }

    public function setClosingDate(?\DateTimeInterface $closingDate): static
    {
        $this->closingDate = $closingDate;

        return $this;
    }
}
