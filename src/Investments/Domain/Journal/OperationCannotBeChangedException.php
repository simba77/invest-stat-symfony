<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

final class OperationCannotBeChangedException extends \DomainException
{
    public static function opening(): self
    {
        return new self('A purchase or a short sale is corrected or deleted through its deal');
    }

    public static function notSale(): self
    {
        return new self('Only the price and the date of a sale can be corrected');
    }

    public static function inFuture(): self
    {
        return new self('The sale cannot be dated in the future');
    }

    public static function breaksJournal(string $problem): self
    {
        return new self(sprintf('The change would break the journal: %s', $problem));
    }
}
