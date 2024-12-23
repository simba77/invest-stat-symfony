<?php

declare(strict_types=1);

namespace App\Domain\Investments\Operations;

use App\Domain\Investments\Accounts\Account;
use App\Domain\Investments\Operations\Deals\DealStatus;
use App\Domain\Investments\Operations\Deals\DealType;
use App\Domain\Shared\CreatedByProvider;
use App\Domain\Shared\CreatedDateProvider;
use App\Domain\Shared\CreatedDateProviderInterface;
use App\Domain\Shared\CreatedUserProviderInterface;
use App\Domain\Shared\UpdatedByProvider;
use App\Domain\Shared\UpdatedDateProvider;
use App\Domain\Shared\UpdatedDateProviderInterface;
use App\Domain\Shared\UpdatedUserProviderInterface;
use App\Domain\Shared\User;
use App\Infrastructure\Persistence\Repository\DealRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DealRepository::class)]
#[ORM\Table(name: 'deals')]
#[ORM\Index(columns: ['user_id', 'status'], name: 'user_status')]
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
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'deals')]
    #[ORM\JoinColumn(nullable: false)]
    private Account $account;

    #[ORM\Column(length: 255)]
    private string $ticker;

    #[ORM\Column(length: 255)]
    private string $stockMarket;

    #[ORM\Column]
    private int $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $buyPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $targetPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $sellPrice;

    #[ORM\Column(type: Types::SMALLINT, enumType: DealStatus::class)]
    private DealStatus $status;

    #[ORM\Column(type: Types::SMALLINT, enumType: DealType::class)]
    private DealType $type;

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
        string      $buyPrice,
        string      $targetPrice = '0',
        string      $sellPrice = '0',
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

    public function getBuyPrice(): ?string
    {
        return $this->buyPrice;
    }

    public function setBuyPrice(string $buyPrice): static
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }

    public function getTargetPrice(): ?string
    {
        return $this->targetPrice;
    }

    public function setTargetPrice(?string $targetPrice): static
    {
        $this->targetPrice = $targetPrice;

        return $this;
    }

    public function getSellPrice(): ?string
    {
        return $this->sellPrice;
    }

    public function setSellPrice(?string $sellPrice): static
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
