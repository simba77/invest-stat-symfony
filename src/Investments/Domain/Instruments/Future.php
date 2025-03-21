<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\FutureRepository;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FutureRepository::class)]
#[ORM\Table(name: 'futures')]
#[ORM\Index(columns: ['ticker', 'stock_market'], name: 'ticker_market')]
#[ORM\Index(columns: ['t_uid'], name: 't_uid')]
class Future implements
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

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $price;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $lotSize = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiration = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $stepPrice = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $prevPrice = null;

    #[ORM\Column(type: Types::GUID, length: 255, nullable: true)]
    private ?string $tUid = null;

    /**
     * @param string $ticker
     * @param string $name
     * @param string $stockMarket
     * @param string $currency
     * @param numeric-string $price
     * @param numeric-string $prevPrice
     * @param string $shortName
     * @param string $latName
     * @param numeric-string $lotSize
     * @param \DateTimeInterface|null $expiration
     * @param numeric-string|null $stepPrice
     */
    public function __construct(
        string $ticker,
        string $name,
        string $stockMarket,
        string $currency,
        string $price,
        string $prevPrice = '0',
        string $shortName = '',
        string $latName = '',
        string $lotSize = '1',
        ?\DateTimeInterface $expiration = null,
        ?string $stepPrice = null,
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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return numeric-string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @param numeric-string $price
     */
    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return numeric-string|null
     */
    public function getLotSize(): ?string
    {
        return $this->lotSize;
    }

    /**
     * @param numeric-string|null $lotSize
     */
    public function setLotSize(?string $lotSize): static
    {
        $this->lotSize = $lotSize;

        return $this;
    }

    /**
     * @return numeric-string|null
     */
    public function getStepPrice(): ?string
    {
        return $this->stepPrice;
    }

    /**
     * @return numeric-string|null
     */
    public function getPrevPrice(): ?string
    {
        return $this->prevPrice;
    }

    /**
     * @param numeric-string|null $prevPrice
     * @return $this
     */
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
