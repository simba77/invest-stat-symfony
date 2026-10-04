<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\ShareRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A share, a depositary receipt or a fund unit.
 */
#[ORM\Entity(repositoryClass: ShareRepository::class)]
class Share extends Instrument
{
    /**
     * @psalm-suppress UnusedProperty
     * @see \App\Investments\Domain\Instruments\Securities\ShareTypeEnum
     */
    #[ORM\Column(name: 'share_type', type: Types::SMALLINT, nullable: true)]
    private ?int $type;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sector = null;

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
        parent::__construct($ticker, $name, $stockMarket, $currency, $price);
        $this->shortName = $shortName;
        $this->latName = $latName;
        $this->lotSize = $lotSize;
        $this->isin = $isin;
        $this->type = $type;
        $this->prevPrice = $prevPrice;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;

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
}
