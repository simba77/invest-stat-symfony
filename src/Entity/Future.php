<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FutureRepository;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FutureRepository::class)]
#[ORM\Table(name: 'futures')]
#[ORM\Index(columns: ['ticker', 'stock_market'], name: 'ticker_market')]
class Future implements
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

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?float $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $lotSize = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiration = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?float $stepPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $prevPrice = null;

    public function __construct(
        string $ticker,
        string $name,
        string $stockMarket,
        string $currency,
        float $price,
        string $prevPrice = '0',
        string $shortName = '',
        string $latName = '',
        float $lotSize = 1,
        ?\DateTimeInterface $expiration = null,
        ?float $stepPrice = null,
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
        $this->expiration = $expiration;
        $this->stepPrice = $stepPrice;
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

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


    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }

    public function setExpiration(?\DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;
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
