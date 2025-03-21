<?php

declare(strict_types=1);

namespace App\Investments\Domain\Analytics;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Infrastructure\Persistence\Repository\StatisticRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticRepository::class)]
class Statistic
{
    /** @psalm-suppress UnusedProperty */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Account $account;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $date;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $balance = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $usdBalance = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $investments = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $currentValue = null;

    /** @psalm-suppress UnusedProperty */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, nullable: true)]
    private ?string $profit = null;

    public function __construct(
        Account $account,
        \DateTimeInterface $date,
        string $balance,
        string $usdBalance,
        string $investments,
        string $currentValue,
        string $profit
    ) {
        $this->account = $account;
        $this->date = $date;
        $this->balance = $balance;
        $this->usdBalance = $usdBalance;
        $this->investments = $investments;
        $this->currentValue = $currentValue;
        $this->profit = $profit;
    }
}
