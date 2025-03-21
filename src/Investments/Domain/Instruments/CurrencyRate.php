<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\CurrencyRateRepository;
use App\Shared\Domain\CreatedDateProvider;
use App\Shared\Domain\CreatedDateProviderInterface;
use App\Shared\Domain\UpdatedDateProvider;
use App\Shared\Domain\UpdatedDateProviderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRateRepository::class)]
#[ORM\Table(name: 'currency_rates')]
class CurrencyRate implements CreatedDateProviderInterface, UpdatedDateProviderInterface
{
    use CreatedDateProvider;
    use UpdatedDateProvider;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 10)]
    private string $baseCurrency;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(length: 10)]
    private string $targetCurrency;

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $rate;

    public function __construct(string $baseCurrency, string $targetCurrency, string $rate)
    {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->rate = $rate;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function setRate(string $rate): static
    {
        $this->rate = $rate;

        return $this;
    }
}
