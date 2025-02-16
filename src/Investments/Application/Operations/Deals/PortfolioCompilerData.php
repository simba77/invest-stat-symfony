<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Operations\Deal;

class PortfolioCompilerData
{
    public function __construct(
        /**
         * @var array<int, array{
         *      deal: Deal,
         *      shareName: string,
         *      sharePrice: string,
         *      sharePrevPrice: string,
         *      shareCurrency: string,
         *      shareType: string,
         *      bondName: string,
         *      bondPrice: string,
         *      bondPrevPrice: string,
         *      bondCurrency: string,
         *      bondLotSize: string,
         *      futureName: string,
         *      futurePrice: string,
         *      futurePrevPrice: string,
         *      futureCurrency: string,
         *      futureStepPrice: string,
         *      futureLotSize: string,
         *  }>
         */
        public array $deals,
        /** @var array<int, array{account: Account, deposits_sum: string | null}> */
        public array $accounts,
    ) {
    }
}
