<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

use App\Http\MoexHttpClient;

class MoexSharesProvider implements SharesProviderInterface
{
    public function __construct(
        private readonly MoexHttpClient $httpClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getShares(): array
    {
        // TQBR Board
        $tqbr = $this->httpClient->getSharesByBoard('TQBR');
        $tqbrShares = $this->parseResult($tqbr);

        // TQTF Board
        $tqtf = $this->httpClient->getSharesByBoard('TQTF');
        $tqtfShares = $this->parseResult($tqtf);

        // TQIF Board
        $tqif = $this->httpClient->getSharesByBoard('TQIF');
        $tqifShares = $this->parseResult($tqif);

        return [...$tqbrShares, ...$tqtfShares, ...$tqifShares];
    }

    private function parseResult(array $boardData)
    {
        $result = [];
        foreach ($boardData['shares'] as $share) {
            $marketData = array_filter($boardData['marketData'], fn($item) => $item['SECID'] === $share['SECID']);
            $price = $marketData[array_key_first($marketData)]['LCURRENTPRICE'] ?? '';

            $result[] = new ShareDTO(
                ticker:      $share['SECID'],
                name:        $share['SECNAME'],
                stockMarket: 'MOEX',
                currency:    'RUB',
                price:       (float) (! empty($price) ? $price : 0),
                type:        ShareTypeEnum::Stock->value,
                shortName:   $share['SHORTNAME'],
                latName:     $share['LATNAME'],
                lotSize:     (float) $share['LOTSIZE'],
                isin:        $share['ISIN']
            );
        }

        return $result;
    }
}
