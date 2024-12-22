<?php

declare(strict_types=1);

namespace App\Domain\Investments\Instruments\Securities;

use App\Domain\Investments\Instruments\MoexHttpClient;

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

    private function parseResult(array $boardData): array
    {
        $result = [];
        foreach ($boardData['shares'] as $share) {
            $marketData = array_filter($boardData['marketData'], fn($item) => $item['SECID'] === $share['SECID']);
            $price = $marketData[array_key_first($marketData)]['LAST'] ?? '';

            $result[] = new ShareDTO(
                ticker:      $share['SECID'],
                name:        $share['SECNAME'],
                stockMarket: 'MOEX',
                currency:    'RUB',
                price:       ! empty($price) ? $price : '0',
                type:        ShareTypeEnum::Stock->value,
                shortName:   $share['SHORTNAME'],
                latName:     $share['LATNAME'],
                lotSize:     ! empty($share['LOTSIZE']) ? $share['LOTSIZE'] : '0',
                isin:        $share['ISIN'],
                prevPrice:   ! empty($share['PREVPRICE']) ? $share['PREVPRICE'] : '0',
                classCode:   $share['BOARDID'],
            );
        }

        return $result;
    }
}
