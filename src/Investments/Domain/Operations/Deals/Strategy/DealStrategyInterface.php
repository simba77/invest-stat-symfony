<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;

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
