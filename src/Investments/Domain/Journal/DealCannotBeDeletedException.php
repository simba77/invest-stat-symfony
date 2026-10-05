<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

final class DealCannotBeDeletedException extends \DomainException
{
    public static function soldPartOfPurchase(int $dealId): self
    {
        return new self(sprintf('Deal %d is a sold part of a purchase; correct the purchase instead', $dealId));
    }
}
