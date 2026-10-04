<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\InstrumentRepository;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A security of the catalogue: one table for every kind, so that deals and payouts refer to
 * an instrument by its id. A ticker is unique on its exchange.
 *
 * Associations should target a kind (Share, Bond, Future) when they can: Doctrine has no lazy
 * proxy for this root class and loads an instrument referenced as Instrument right away.
 */
#[ORM\Entity(repositoryClass: InstrumentRepository::class)]
#[ORM\Table(name: 'instruments')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'kind', type: 'string', length: 16)]
#[ORM\DiscriminatorMap(['share' => Share::class, 'bond' => Bond::class, 'future' => Future::class])]
#[ORM\UniqueConstraint(name: 'instrument_market_ticker', columns: ['stock_market', 'ticker'])]
#[ORM\Index(columns: ['ticker'], name: 'instrument_ticker')]
#[ORM\Index(columns: ['t_uid'], name: 'instrument_t_uid')]
abstract class Instrument implements
    CreatedDateProviderInterface,
    UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    protected string $ticker;

    #[ORM\Column(length: 255)]
    protected string $name;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $shortName = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $latName = null;

    #[ORM\Column(length: 16)]
    protected string $stockMarket;

    #[ORM\Column(length: 8)]
    protected string $currency;

    /** @var numeric-string */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    protected string $price;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    protected ?string $prevPrice = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    protected ?string $lotSize = null;

    #[ORM\Column(length: 32, nullable: true)]
    protected ?string $isin = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 32, nullable: true)]
    protected ?string $classCode = null;

    #[ORM\Column(length: 64, nullable: true)]
    protected ?string $tUid = null;

    /**
     * @param numeric-string $price
     */
    protected function __construct(string $ticker, string $name, string $stockMarket, string $currency, string $price)
    {
        $this->ticker = $ticker;
        $this->name = $name;
        $this->stockMarket = $stockMarket;
        $this->currency = $currency;
        $this->price = $price;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
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
    public function getPrevPrice(): ?string
    {
        return $this->prevPrice;
    }

    /**
     * @param numeric-string|null $prevPrice
     */
    public function setPrevPrice(?string $prevPrice): static
    {
        $this->prevPrice = $prevPrice;

        return $this;
    }

    /**
     * For a bond, its nominal.
     *
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

    public function getIsin(): ?string
    {
        return $this->isin;
    }

    public function setIsin(?string $isin): static
    {
        $this->isin = $isin;

        return $this;
    }

    public function setClassCode(?string $classCode): static
    {
        $this->classCode = $classCode;

        return $this;
    }

    /**
     * The instrument uid at T-Bank, by which prices are updated and broker operations matched.
     */
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
