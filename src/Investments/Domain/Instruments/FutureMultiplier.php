<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\FutureMultiplierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'future_multipliers')]
#[ORM\UniqueConstraint(name: 'ticker', columns: ['ticker'])]
#[ORM\Entity(repositoryClass: FutureMultiplierRepository::class)]
class FutureMultiplier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private string $ticker = '';

    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 4)]
    private string $value = '';

    public function __construct(string $ticker, string $value)
    {
        $this->ticker = $ticker;
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }

    public function setTicker(string $ticker): void
    {
        $this->ticker = $ticker;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
