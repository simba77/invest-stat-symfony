<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Domain\Operations\Deal;

class DealListCurrencies
{
    /** @var array<string, array{code: string, name: string}> */
    private array $currencies = [];

    /**
     * @param array{
     *      deal: Deal,
     *      shareName: string,
     *      sharePrice: string,
     *      sharePrevPrice: string,
     *      shareCurrency: ?string,
     *      shareType: string,
     *      bondName: string,
     *      bondPrice: string,
     *      bondPrevPrice: string,
     *      bondCurrency: ?string,
     *      bondLotSize: string,
     *      futureName: string,
     *      futurePrice: string,
     *      futurePrevPrice: string,
     *      futureCurrency: ?string,
     *      futureStepPrice: string,
     *      futureLotSize: string,
     *  } $deal
     * @return array{code: string, name: string}
     */
    public function add(array $deal): array
    {
        $currencyCode = $deal['shareCurrency'] ?? $deal['bondCurrency'] ?? $deal['futureCurrency'] ?? 'RUB';
        $currencies = [
            'USD' => [
                'code' => 'USD',
                'name' => 'US Dollar',
            ],
            'RUB' => [
                'code' => 'RUB',
                'name' => 'Russian Rouble',
            ],
        ];

        $currency = $currencies[$currencyCode];
        $this->currencies[$currency['code']] = $currency;
        return $currency;
    }

    /** @return  array<string, array{code: string, name: string}> */
    public function getCurrencies(): array
    {
        return $this->currencies;
    }
}
