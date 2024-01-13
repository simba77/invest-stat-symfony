<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BondRepository;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BondRepository::class)]
#[ORM\Table(name: 'bonds')]
#[ORM\Index(columns: ['ticker', 'stock_market'], name: 'ticker_market')]
class Bond implements
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ticker = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $shortName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latName = null;

    #[ORM\Column(length: 255)]
    private ?string $stockMarket = null;

    #[ORM\Column(length: 255)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $lotSize = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?float $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $stepPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $couponPercent = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $couponValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $couponAccumulated = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextCouponDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $maturityDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $prevPrice = null;

    public function __construct(
        string $ticker,
        string $name,
        string $stockMarket,
        string $currency,
        float $price,
        string $prevPrice = '',
        string $shortName = '',
        string $latName = '',
        float $lotSize = 1,
        ?float $stepPrice = null,
        ?float $couponPercent = null,
        ?float $couponValue = null,
        ?float $couponAccumulated = null,
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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
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

    public function getLatName(): ?string
    {
        return $this->latName;
    }

    public function setLatName(?string $latName): static
    {
        $this->latName = $latName;

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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getLotSize(): ?float
    {
        return $this->lotSize;
    }

    public function setLotSize(?float $lotSize): static
    {
        $this->lotSize = $lotSize;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStepPrice(): ?float
    {
        return $this->stepPrice;
    }

    public function setStepPrice(?float $stepPrice): static
    {
        $this->stepPrice = $stepPrice;

        return $this;
    }

    public function getCouponPercent(): ?float
    {
        return $this->couponPercent;
    }

    public function setCouponPercent(?float $couponPercent): static
    {
        $this->couponPercent = $couponPercent;

        return $this;
    }

    public function getCouponValue(): ?float
    {
        return $this->couponValue;
    }

    public function setCouponValue(?float $couponValue): static
    {
        $this->couponValue = $couponValue;

        return $this;
    }

    public function getCouponAccumulated(): ?float
    {
        return $this->couponAccumulated;
    }

    public function setCouponAccumulated(?float $couponAccumulated): static
    {
        $this->couponAccumulated = $couponAccumulated;

        return $this;
    }

    public function getNextCouponDate(): ?\DateTimeInterface
    {
        return $this->nextCouponDate;
    }

    public function setNextCouponDate(?\DateTimeInterface $nextCouponDate): static
    {
        $this->nextCouponDate = $nextCouponDate;

        return $this;
    }

    public function getMaturityDate(): ?\DateTimeInterface
    {
        return $this->maturityDate;
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
}
