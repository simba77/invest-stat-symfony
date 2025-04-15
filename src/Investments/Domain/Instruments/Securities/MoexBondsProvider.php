<?php

declare(strict_types=1);

namespace App\Investments\Domain\Instruments\Securities;

use App\Investments\Infrastructure\Http\MoexHttpClient;
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
    #[\Override]
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
                price:             ! empty($price) ? $price : '0',
                prevPrice:         ! empty($bond['PREVPRICE']) ? $bond['PREVPRICE'] : '0',
                shortName:         $bond['SHORTNAME'],
                latName:           $bond['LATNAME'],
                lotSize:           ! empty($bond['LOTVALUE']) ? $bond['LOTVALUE'] : '0',
                stepPrice:         ! empty($bond['MINSTEP']) ? $bond['MINSTEP'] : '0',
                couponPercent:     ! empty($bond['COUPONPERCENT']) ? $bond['COUPONPERCENT'] : '0',
                couponValue:       ! empty($bond['COUPONVALUE']) ? $bond['COUPONVALUE'] : '0',
                couponAccumulated: ! empty($bond['ACCRUEDINT']) ? $bond['ACCRUEDINT'] : '0',
                nextCouponDate:    $nextCouponDate->isCurrentCentury() ? $nextCouponDate : null,
                maturityDate:      $maturityDate->isCurrentCentury() ? $maturityDate : null,
            );
        }

        return $result;
    }
}
