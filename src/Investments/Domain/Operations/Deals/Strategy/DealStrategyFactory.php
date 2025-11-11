<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations\Deals\Strategy;

use App\Investments\Domain\Instruments\FutureMultiplierRepositoryInterface;
use App\Investments\Domain\Operations\Deal;

class DealStrategyFactory
{
    public static function create(Deal $deal, ?FutureMultiplierRepositoryInterface $futureMultiplierRepository = null): DealStrategyInterface
    {
        if ($deal->getFuture()) {
            $strategy = new FutureStrategy($deal);
            if ($futureMultiplierRepository !== null) {
                $strategy->setFutureMultiplierRepository($futureMultiplierRepository);
            }
            return $strategy;
        } elseif ($deal->getBond()) {
            return new BondStrategy($deal);
        }
        return new ShareStrategy($deal);
    }
}
