<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments;

use App\Investments\Infrastructure\Persistence\Repository\ShareSplitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * From the trade date on, `before` shares of the issuer count as `after` shares.
 * Brokers do not report splits as operations, so positions are adjusted with these records.
 */
#[ORM\Entity(repositoryClass: ShareSplitRepository::class)]
#[ORM\Table(name: 'share_splits')]
#[ORM\UniqueConstraint(name: 'share_split_ticker_date', columns: ['ticker', 'stock_market', 'trade_date'])]
class ShareSplit
{
    /** @psalm-suppress UnusedProperty */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $ticker;

    #[ORM\Column(length: 16)]
    private string $stockMarket;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $tradeDate;

    #[ORM\Column(name: 'shares_before')]
    private int $before;

    #[ORM\Column(name: 'shares_after')]
    private int $after;

    public function __construct(string $ticker, string $stockMarket, \DateTimeImmutable $tradeDate, int $before, int $after)
    {
        if ($before <= 0 || $after <= 0) {
            throw new \InvalidArgumentException('A split ratio must be positive');
        }

        $this->ticker = $ticker;
        $this->stockMarket = $stockMarket;
        $this->tradeDate = $tradeDate;
        $this->before = $before;
        $this->after = $after;
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }

    public function getStockMarket(): string
    {
        return $this->stockMarket;
    }

    public function getTradeDate(): \DateTimeImmutable
    {
        return $this->tradeDate;
    }

    public function getBefore(): int
    {
        return $this->before;
    }

    public function getAfter(): int
    {
        return $this->after;
    }
}
