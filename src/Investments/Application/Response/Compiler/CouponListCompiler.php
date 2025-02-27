<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Operations\CouponListItemDTO;
use App\Investments\Domain\Operations\Coupon;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<iterable<Coupon>, CouponListItemDTO[]>
 */
class CouponListCompiler implements CompilerInterface
{
    /**
     * @param iterable<Coupon> $entry
     * @return CouponListItemDTO[]
     */
    #[\Override]
    public function compile(mixed $entry): array
    {
        $result = [];
        foreach ($entry as $coupon) {
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
