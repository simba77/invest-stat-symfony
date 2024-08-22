<?php

declare(strict_types=1);

namespace App\Services\Deals\Strategy;

use App\Entity\Account;
use App\Entity\Deal;
use App\Services\MarketData\Securities\SecurityTypeEnum;

interface DealStrategyInterface
{
    /** @param array{
     *     deal: Deal,
     *     shareName?: string,
     *     bondName?: string,
     *     futureName?: string,
     *     sharePrice?: string,
     *     sharePrevPrice?: string,
     *     bondPrice?: string,
     *     bondPrevPrice?: string,
     *     bondLotSize?: string,
     *     futurePrice?: string,
     *     futurePrevPrice?: string,
     *     futureStepPrice?: string,
     *     futureLotSize?: string,
     *     shareCurrency?: string,
     *     futureCurrency?: string,
     *     bondCurrency?: string,
     * } $deal
     */
    public function __construct(array $deal, Account $account);

    public function getName(): string;

    public function getSecurityType(): SecurityTypeEnum;

    public function getBuyPrice(): string;

    public function getSellPrice(): string;

    public function getCurrentPrice(): string;

    public function getPrevPrice(): string;

    public function getCommission(string $price, string $quantity): string;

    public function getCurrency(): string;
}
