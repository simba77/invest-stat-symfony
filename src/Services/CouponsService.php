<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Repository\CouponRepository;
use App\Response\DTO\Coupons\CouponListItemDTO;
use Doctrine\Common\Collections\Criteria;

class CouponsService
{
    public function __construct(
        private readonly CouponRepository $couponRepository
    ) {
    }

    /**
     * @param User|null $user
     * @return CouponListItemDTO[]
     */
    public function getCouponsForUser(?User $user): array
    {
        $coupons = $this->couponRepository->findBy(['user' => $user], ['date' => Criteria::DESC, 'id' => Criteria::DESC]);
        $result = [];
        foreach ($coupons as $coupon) {
            $result[] = new CouponListItemDTO(
                id:          $coupon->getId(),
                date:        $coupon->getDate()->format('d.m.Y'),
                ticker:      $coupon->getTicker(),
                stockMarket: $coupon->getStockMarket(),
                amount:      $coupon->getAmount(),
                accountName: $coupon->getAccount()->getName()
            );
        }
        return $result;
    }
}
