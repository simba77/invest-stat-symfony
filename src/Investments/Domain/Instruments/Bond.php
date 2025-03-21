<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\BondRepository;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BondRepository::class)]
#[ORM\Table(name: 'bonds')]
#[ORM\Index(columns: ['ticker', 'stock_market'], name: 'ticker_market')]
#[ORM\Index(columns: ['t_uid'], name: 't_uid')]
class Bond implements
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $ticker;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortName = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latName = null;

    #[ORM\Column(length: 255)]
    private string $stockMarket;

    #[ORM\Column(length: 255)]
    private string $currency;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $lotSize = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $price;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $stepPrice = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $couponPercent = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $couponValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $couponAccumulated = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextCouponDate = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $maturityDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $prevPrice = null;

    #[ORM\Column(type: Types::GUID, length: 255, nullable: true)]
    private ?string $tUid = null;

    public function __construct(
        string $ticker,
        string $name,
        string $stockMarket,
        string $currency,
        string $price,
        ?string $prevPrice = null,
        ?string $shortName = null,
        ?string $latName = null,
        ?string $lotSize = '1',
        ?string $stepPrice = null,
        ?string $couponPercent = null,
        ?string $couponValue = null,
        ?string $couponAccumulated = null,
        ?\DateTimeInterface $nextCouponDate = null,
        ?\DateTimeInterface $maturityDate = null,
    ) {
        $this->ticker = $ticker;
        $this->name = $name;
        $this->shortName = $shortName;
        $this->latName = $latName;
        $this->stockMarket = $stockMarket;
        $this->currency = $currency;
        $this->price = $price;
        $this->prevPrice = $prevPrice;
        $this->lotSize = $lotSize;
        $this->stepPrice = $stepPrice;
        $this->couponPercent = $couponPercent;
        $this->couponValue = $couponValue;
        $this->couponAccumulated = $couponAccumulated;
        $this->nextCouponDate = $nextCouponDate;
        $this->maturityDate = $maturityDate;
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(?string $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function setLatName(?string $latName): static
    {
        $this->latName = $latName;

        return $this;
    }

    public function getStockMarket(): string
    {
        return $this->stockMarket;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getLotSize(): ?string
    {
        return $this->lotSize;
    }

    public function setLotSize(?string $lotSize): static
    {
        $this->lotSize = $lotSize;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function setCouponPercent(?string $couponPercent): static
    {
        $this->couponPercent = $couponPercent;

        return $this;
    }

    public function setCouponValue(?string $couponValue): static
    {
        $this->couponValue = $couponValue;

        return $this;
    }

    public function getCouponAccumulated(): ?string
    {
        return $this->couponAccumulated;
    }

    public function setCouponAccumulated(?string $couponAccumulated): static
    {
        $this->couponAccumulated = $couponAccumulated;

        return $this;
    }

    public function setNextCouponDate(?\DateTimeInterface $nextCouponDate): static
    {
        $this->nextCouponDate = $nextCouponDate;

        return $this;
    }

    public function setMaturityDate(?\DateTimeInterface $maturityDate): static
    {
        $this->maturityDate = $maturityDate;

        return $this;
    }

    public function getPrevPrice(): ?string
    {
        return $this->prevPrice;
    }

    public function setPrevPrice(?string $prevPrice): static
    {
        $this->prevPrice = $prevPrice;

        return $this;
    }

    public function getTUid(): ?string
    {
        return $this->tUid;
    }

    public function setTUid(?string $tUid): static
    {
        $this->tUid = $tUid;

        return $this;
    }
}
