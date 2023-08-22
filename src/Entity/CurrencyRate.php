<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CurrencyRateRepository;
use App\Shared\CreatedDateProvider;
use App\Shared\CreatedDateProviderInterface;
use App\Shared\UpdatedDateProvider;
use App\Shared\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRateRepository::class)]
class CurrencyRate implements CreatedDateProviderInterface, UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $baseCurrency = null;

    #[ORM\Column(length: 10)]
    private ?string $targetCurrency = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private ?float $rate = null;

    public function __construct(string $baseCurrency, string $targetCurrency, float $rate)
    {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->rate = $rate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseCurrency(): ?string
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(string $baseCurrency): static
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    public function getTargetCurrency(): ?string
    {
        return $this->targetCurrency;
    }

    public function setTargetCurrency(string $targetCurrency): static
    {
        $this->targetCurrency = $targetCurrency;

        return $this;
    }

    public function getRate(): float
    {
        return $this->rate ?? 0;
    }

    public function setRate(float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }
}
