<?php

declare(strict_types=1);

namespace App\Services\MarketData\Securities;

use App\Http\MoexHttpClient;
use Carbon\Carbon;

class MoexBondsProvider implements BondsProviderInterface
{
    public function __construct(
        private readonly MoexHttpClient $httpClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getBonds(): array
    {
        $bonds = $this->httpClient->getBonds();

        $result = [];
        foreach ($bonds['bonds'] as $bond) {
            $marketData = array_filter($bonds['marketData'], fn($item) => $item['SECID'] === $bond['SECID']);
            $price = $marketData[array_key_first($marketData)]['LCURRENTPRICE'] ?? '';

            $maturityDate = Carbon::parse($bond['MATDATE']);
            $nextCouponDate = Carbon::parse($bond['NEXTCOUPON']);

            $result[] = new BondDTO(
                ticker:            $bond['SECID'],
                name:              $bond['SECNAME'],
                stockMarket:       'MOEX',
                currency:          'RUB',
                price:             (float) (! empty($price) ? $price : 0),
                shortName:         $bond['SHORTNAME'],
                latName:           $bond['LATNAME'],
                lotSize:           (float) $bond['LOTVALUE'],
                stepPrice:         (float) $bond['MINSTEP'],
                couponPercent:     (float) $bond['COUPONPERCENT'],
                couponValue:       (float) $bond['COUPONVALUE'],
                couponAccumulated: (float) $bond['ACCRUEDINT'],
                nextCouponDate:    $nextCouponDate->isCurrentCentury() ? $nextCouponDate : null,
                maturityDate:      $maturityDate->isCurrentCentury() ? $maturityDate : null,
            );
        }

        return $result;
    }
}
