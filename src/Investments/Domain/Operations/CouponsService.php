<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Application\Response\DTO\Operations\CouponListItemDTO;
use App\Investments\Infrastructure\Persistence\Repository\CouponRepository;
use App\Shared\Domain\User;
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
