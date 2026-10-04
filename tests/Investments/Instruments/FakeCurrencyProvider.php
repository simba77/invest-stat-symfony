<?php

declare(strict_types=1);

namespace App\Tests\Investments\Instruments;

use App\Investments\Domain\Instruments\Currencies\CurrencyProviderInterface;
use App\Investments\Domain\Instruments\Currencies\CurrencyRateDTO;

final class FakeCurrencyProvider implements CurrencyProviderInterface
{
    /** @var list<CurrencyRateDTO> */
    public array $current = [];

    /** @var list<CurrencyRateDTO> */
    public array $history = [];

    /** @var list<array{string, string}> the periods the history was asked for */
    public array $historyRequests = [];

    #[\Override]
    public function getCurrencyRates(): array
    {
        return $this->current;
    }

    #[\Override]
    public function getRateHistory(\DateTimeImmutable $from, \DateTimeImmutable $till): array
    {
        $this->historyRequests[] = [$from->format('Y-m-d'), $till->format('Y-m-d')];

        return array_values(array_filter(
            $this->history,
            static fn (CurrencyRateDTO $rate) => $rate->date >= $from && $rate->date <= $till,
        ));
    }

    public static function rate(string $currency, string $rate, string $date): CurrencyRateDTO
    {
        return new CurrencyRateDTO('RUB', $currency, $rate, new \DateTimeImmutable($date));
    }
}
