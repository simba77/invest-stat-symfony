<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\FutureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A futures contract, quoted in points.
 */
#[ORM\Entity(repositoryClass: FutureRepository::class)]
class Future extends Instrument
{
    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiration = null;

    /** @var numeric-string|null */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $stepPrice = null;

    /**
     * Roubles per point set by the owner, when the step price of the exchange does not value deals right.
     *
     * @var numeric-string|null
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
    private ?string $multiplier = null;

    /**
     * @param numeric-string $price
     * @param numeric-string $prevPrice
     * @param numeric-string $lotSize
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
        parent::__construct($ticker, $name, $stockMarket, $currency, $price);
        $this->prevPrice = $prevPrice;
        $this->shortName = $shortName;
        $this->latName = $latName;
        $this->lotSize = $lotSize;
        $this->expiration = $expiration;
        $this->stepPrice = $stepPrice;
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
    public function getMultiplier(): ?string
    {
        return $this->multiplier;
    }

    /**
     * @param numeric-string|null $multiplier
     */
    public function setMultiplier(?string $multiplier): static
    {
        $this->multiplier = $multiplier;

        return $this;
    }

    /**
     * What a point of the price is worth: the multiplier set by the owner, else the step price.
     *
     * @return numeric-string
     */
    public function getPointValue(): string
    {
        return $this->multiplier ?? $this->stepPrice ?? '1';
    }
}
