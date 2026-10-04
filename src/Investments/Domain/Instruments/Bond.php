<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\BondRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A bond, quoted in percent of its nominal; the nominal is kept as the lot size.
 */
#[ORM\Entity(repositoryClass: BondRepository::class)]
class Bond extends Instrument
{
    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(name: 'price_step', type: Types::DECIMAL, precision: 18, scale: 4, nullable: true)]
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
        parent::__construct($ticker, $name, $stockMarket, $currency, $price);
        $this->shortName = $shortName;
        $this->latName = $latName;
        $this->prevPrice = $prevPrice;
        $this->lotSize = $lotSize;
        $this->stepPrice = $stepPrice;
        $this->couponPercent = $couponPercent;
        $this->couponValue = $couponValue;
        $this->couponAccumulated = $couponAccumulated;
        $this->nextCouponDate = $nextCouponDate;
        $this->maturityDate = $maturityDate;
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
}
