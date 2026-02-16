<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Securities\SecurityTypeEnum;
use App\Investments\Domain\Operations\Deal;

interface DealStrategyInterface
{
    public function __construct(Deal $deal);

    public function getName(): string;

    public function getSecurityType(): SecurityTypeEnum;

    public function getBuyPrice(): string;

    public function getSellPrice(): string;

    public function getCurrentPrice(): string;

    public function getPrevPrice(): string;

    public function getCommission(string $price, string $quantity): string;

    public function getCurrency(): string;

    public function getInstrumentId(): ?int;
}
