<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Currencies;

use App\Investments\Infrastructure\Http\MoexHttpClient;

class MoexCurrencyProvider implements CurrencyProviderInterface
{
    private const array SEC_IDS = [
        'USD/RUB',
        'HKD/RUB',
        'EUR/RUB',
        'CNY/RUB',
    ];

    public function __construct(
        private readonly MoexHttpClient $httpClient
    ) {
    }

    /**
     * MOEX publishes a rate for every clearing session; the latest one is the current rate.
     * Clearing codes are not relied on: MOEX has renamed them before.
     *
     * @return CurrencyRateInterface[]
     */
    #[\Override]
    public function getCurrencyRates(): array
    {
        $latest = [];
        foreach ($this->httpClient->getCurrencyRates() as $item) {
            $secId = $item['secid'];
            if (! in_array($secId, self::SEC_IDS, true)) {
                continue;
            }

            $moment = $item['tradedate'] . ' ' . $item['tradetime'];
            if (! isset($latest[$secId]) || $latest[$secId]['moment'] < $moment) {
                $latest[$secId] = ['moment' => $moment, 'rate' => $item['rate'], 'date' => $item['tradedate']];
            }
        }

        $result = [];
        foreach ($latest as $secId => $item) {
            $currency = explode('/', $secId);

            $result[] = new CurrencyRateDTO(
                $currency[1], $currency[0], $item['rate'], new \DateTimeImmutable($item['date'])
            );
        }

        return $result;
    }

    /**
     * MOEX sets the rate several times a day; the day keeps the last one.
     */
    #[\Override]
    public function getRateHistory(\DateTimeImmutable $from, \DateTimeImmutable $till): array
    {
        $result = [];
        foreach (self::SEC_IDS as $secId) {
            $days = [];
            foreach ($this->httpClient->getCurrencyRateHistory($secId, $from, $till) as $item) {
                $day = $item['tradedate'];
                if (! isset($days[$day]) || $days[$day]['time'] < $item['tradetime']) {
                    $days[$day] = ['time' => $item['tradetime'], 'rate' => $item['rate']];
                }
            }

            $currency = explode('/', $secId);
            foreach ($days as $day => $item) {
                $result[] = new CurrencyRateDTO($currency[1], $currency[0], $item['rate'], new \DateTimeImmutable((string) $day));
            }
        }

        return $result;
    }
}
