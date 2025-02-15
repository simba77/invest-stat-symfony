<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

use App\Shared\Domain\User;

class UpdateCouponCommand
{
    public function __construct(
        public int $id,
        public int $accountId,
        public string $date,
        public string $amount,
        public string $ticker,
        public string $stockMarket,
        public User $user,
    ) {
    }
}
