<?php

declare(strict_types=1);

namespace App\Investments\Application\Response\Compiler;

use App\Investments\Application\Response\DTO\Operations\CouponFormDTO;
use App\Investments\Domain\Operations\Coupon;
use App\Shared\Infrastructure\Compiler\CompilerInterface;

/**
 * @template-implements CompilerInterface<Coupon, CouponFormDTO>
 */
class CouponFormCompiler implements CompilerInterface
{
    #[\Override]
    public function compile(mixed $entry): CouponFormDTO
    {
        return new CouponFormDTO(
            id:          $entry->getId(),
            amount:      $entry->getAmount(),
            date:        $entry->getDate()->format('Y-m-d'),
            accountId:   $entry->getAccount()->getId(),
            ticker:      $entry->getTicker(),
            stockMarket: $entry->getStockMarket(),
        );
    }
}
