<?php

declare(strict_types=1);

namespace App\Services\MarketData\Currencies;

use App\Http\MoexHttpClient;

class MoexCurrencyProvider implements CurrencyProviderInterface
{
    public function __construct(
        private readonly MoexHttpClient $httpClient
    ) {
    }

    /**
     * @return CurrencyRateInterface[]
     */
    public function getCurrencyRates(): array
    {
        $allRates = $this->httpClient->getCurrencyRates();
        $secIds = [
            'USD/RUB',
            'HKD/RUB',
            'EUR/RUB',
            'CNY/RUB',
        ];

        $rates = array_filter($allRates, fn($item) => in_array($item['secid'], $secIds) && $item['clearing'] === 'pk');

        $result = [];
        foreach ($rates as $item) {
            $currency = explode('/', $item['secid']);

            $result[] = new CurrencyRateDTO(
                $currency[1], $currency[0], (float) $item['rate']
            );
        }

        return $result;
    }
}
