<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

use App\Investments\Domain\Instruments\MoexHttpClient;
use Carbon\Carbon;

class MoexFuturesProvider implements FuturesProviderInterface
{
    public function __construct(
        private readonly MoexHttpClient $httpClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getFutures(): array
    {
        $futures = $this->httpClient->getFutures();

        $result = [];
        foreach ($futures['futures'] as $future) {
            $marketData = array_filter($futures['marketData'], fn($item) => $item['SECID'] === $future['SECID']);
            $price = $marketData[array_key_first($marketData)]['LAST'] ?? '';

            $result[] = new FutureDTO(
                ticker:      $future['SECID'],
                name:        $future['SECNAME'],
                stockMarket: 'MOEX',
                currency:    'RUB',
                price:       ! empty($price) ? $price : '0',
                prevPrice:   ! empty($future['PREVPRICE']) ? $future['PREVPRICE'] : '0',
                shortName:   $future['SHORTNAME'],
                latName:     $future['LATNAME'],
                lotSize:     ! empty($future['LOTVOLUME']) ? $future['LOTVOLUME'] : '0',
                expiration:  Carbon::parse($future['LASTDELDATE']),
                stepPrice:   ! empty($future['STEPPRICE']) ? $future['STEPPRICE'] : '0'
            );
        }

        return $result;
    }
}
