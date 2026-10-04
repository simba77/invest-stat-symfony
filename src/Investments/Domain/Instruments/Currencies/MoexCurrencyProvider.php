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
                $latest[$secId] = ['moment' => $moment, 'rate' => $item['rate']];
            }
        }

        $result = [];
        foreach ($latest as $secId => $item) {
            $currency = explode('/', $secId);

            $result[] = new CurrencyRateDTO(
                $currency[1], $currency[0], $item['rate']
            );
        }

        return $result;
    }
}
