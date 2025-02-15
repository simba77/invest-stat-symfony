<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Coupons;

class DeleteCouponCommand
{
    public function __construct(
        public int $id,
    ) {
    }
}
