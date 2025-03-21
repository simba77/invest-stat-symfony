<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Investments\Infrastructure\Persistence\Repository\DealRepository;
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

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $buyPrice;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $targetPrice;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $sellPrice;

    #[ORM\Column(type: Types::SMALLINT, enumType: DealStatus::class)]
    private DealStatus $status;

    #[ORM\Column(type: Types::SMALLINT, enumType: DealType::class)]
    private DealType $type;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $closingDate = null;

    #[ORM\ManyToOne(targetEntity: Share::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Share $share = null;

    #[ORM\ManyToOne(targetEntity: Bond::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Bond $bond = null;

    #[ORM\ManyToOne(targetEntity: Future::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Future $future = null;

    /**
     * @param User $user
     * @param Account $account
     * @param string $ticker
     * @param string $stockMarket
     * @param DealStatus $status
     * @param DealType $type
     * @param int $quantity
     * @param numeric-string $buyPrice
     * @param numeric-string $targetPrice
     * @param numeric-string $sellPrice
     */
    public function __construct(
        User $user,
        Account $account,
        string $ticker,
        string $stockMarket,
        DealStatus $status,
        DealType $type,
        int $quantity,
        string $buyPrice,
        string $targetPrice = '0',
        string $sellPrice = '0',
    ) {
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getTicker(): string
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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return numeric-string
     */
    public function getBuyPrice(): string
    {
        return $this->buyPrice;
    }

    /**
     * @param numeric-string $buyPrice
     * @return $this
     */
    public function setBuyPrice(string $buyPrice): static
    {
        $this->buyPrice = $buyPrice;

        return $this;
    }

    /**
     * @return numeric-string|null
     */
    public function getTargetPrice(): ?string
    {
        return $this->targetPrice;
    }

    /**
     * @param numeric-string|null $targetPrice
     * @return $this
     */
    public function setTargetPrice(?string $targetPrice): static
    {
        $this->targetPrice = $targetPrice;

        return $this;
    }

    /**
     * @return numeric-string|null
     */
    public function getSellPrice(): ?string
    {
        return $this->sellPrice;
    }

    /**
     * @param numeric-string|null $sellPrice
     * @return $this
     */
    public function setSellPrice(?string $sellPrice): static
    {
        $this->sellPrice = $sellPrice;

        return $this;
    }

    public function getStatus(): DealStatus
    {
        return $this->status;
    }

    public function setStatus(DealStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): DealType
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

    public function getShare(): ?Share
    {
        return $this->share;
    }

    public function getBond(): ?Bond
    {
        return $this->bond;
    }

    public function getFuture(): ?Future
    {
        return $this->future;
    }

    public function setShare(?Share $share): static
    {
        $this->share = $share;
        return $this;
    }

    public function setBond(?Bond $bond): static
    {
        $this->bond = $bond;
        return $this;
    }

    public function setFuture(?Future $future): static
    {
        $this->future = $future;
        return $this;
    }
}
