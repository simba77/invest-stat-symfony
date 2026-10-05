<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

use App\Investments\Domain\Operations\Deals\DealType;

/**
 * What the owner recorded in the journal of a manual account.
 */
enum ManualOperationType: string
{
    /** Opens a long lot. */
    case Buy = 'buy';
    /** Opens a short lot. */
    case Short = 'short';
    /** Closes a lot given by its key, or the oldest lots of the security that are not blocked. */
    case Close = 'close';
    case Block = 'block';
    case Unblock = 'unblock';
    /** Brings the cash to what the broker shows. */
    case CashAdjustment = 'cash_adjustment';
    /** Blocks a sum of the cash in a currency, or frees it when negative: the money stays, but cannot be used. */
    case BlockCash = 'block_cash';

    public static function opening(DealType $direction): self
    {
        return $direction === DealType::Short ? self::Short : self::Buy;
    }

    public function direction(): ?DealType
    {
        return match ($this) {
            self::Buy => DealType::Long,
            self::Short => DealType::Short,
            default => null,
        };
    }
}
