<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Operations\Deal;

class DealStrategyFactory
{
    public static function create(Deal $deal): DealStrategyInterface
    {
        if ($deal->getFuture()) {
            return new FutureStrategy($deal);
        } elseif ($deal->getBond()) {
            return new BondStrategy($deal);
        }
        return new ShareStrategy($deal);
    }
}
