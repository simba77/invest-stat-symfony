<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

interface CouponRepositoryInterface
{
    /**
     * @return array<Coupon>
     */
    public function findAll(): array;
}
