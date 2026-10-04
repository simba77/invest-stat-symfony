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

/**
 * How much of the base currency one unit of the target currency cost on an exchange day.
 * The rate of the latest day values what is held now; past rates value past deals and payouts.
 */
#[ORM\Entity(repositoryClass: CurrencyRateRepository::class)]
#[ORM\Table(name: 'currency_rates')]
#[ORM\UniqueConstraint(name: 'currency_rate_day', columns: ['base_currency', 'target_currency', 'date'])]
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    public function __construct(string $baseCurrency, string $targetCurrency, string $rate, \DateTimeImmutable $date)
    {
        $this->baseCurrency = $baseCurrency;
        $this->targetCurrency = $targetCurrency;
        $this->rate = $rate;
        $this->date = $date->setTime(0, 0);
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

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }
}
