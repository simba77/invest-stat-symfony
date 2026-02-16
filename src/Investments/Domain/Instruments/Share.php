<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Domain\Instruments\Currencies\Currency;
use App\Investments\Infrastructure\Persistence\Repository\ShareRepository;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShareRepository::class)]
#[ORM\Table(name: 'shares')]
#[ORM\Index(columns: ['ticker', 'stock_market'], name: 'ticker_market')]
#[ORM\Index(columns: ['t_uid'], name: 't_uid')]
class Share implements
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

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $price;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $lotSize = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $isin = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $type;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $prevPrice = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classCode = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tUid = null;

    public function __construct(
        string $ticker,
        string $name,
        string $stockMarket,
        string $currency,
        string $price,
        int $type,
        string $shortName = '',
        string $latName = '',
        string $lotSize = '1',
        string $isin = '',
        string $prevPrice = '0',
    ) {
        $this->ticker = $ticker;
        $this->name = $name;
        $this->shortName = $shortName;
        $this->latName = $latName;
        $this->stockMarket = $stockMarket;
        $this->currency = $currency;
        $this->price = $price;
        $this->lotSize = $lotSize;
        $this->isin = $isin;
        $this->type = $type;
        $this->prevPrice = $prevPrice;
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

    public function getCurrency(): ?string
    {
        return $this->currency;
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

    public function getLotSize(): ?string
    {
        return $this->lotSize;
    }

    public function setLotSize(?string $lotSize): static
    {
        $this->lotSize = $lotSize;

        return $this;
    }

    public function setIsin(?string $isin): static
    {
        $this->isin = $isin;

        return $this;
    }

    public function getIsin(): ?string
    {
        return $this->isin;
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

    public function setClassCode(?string $classCode): static
    {
        $this->classCode = $classCode;

        return $this;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;

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

    public function getPriceDifference(): string
    {
        $prev = $this->prevPrice ?? '0';

        return bcsub($this->price, $prev, 4);
    }

    public function getPriceChangePercent(): string
    {
        $prev = $this->prevPrice ?? '0';

        if (bccomp($prev, '0', 4) === 0) {
            return '0';
        }

        $difference = bcsub($this->price, $prev, 4);
        return bcmul(bcdiv($difference, $prev, 8), '100', 4);
    }

    public function getPriceTrend(): PriceTrendEnum
    {
        return PriceTrendEnum::fromPrices($this->getPrice(), $this->getPrevPrice() ?? '0');
    }

    public function getCurrencyEnum(): Currency
    {
        return Currency::from($this->currency);
    }
}
