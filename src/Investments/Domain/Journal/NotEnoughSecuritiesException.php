<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

/**
 * A sale by quantity asked for more securities than the open lots that are not blocked hold.
 */
final class NotEnoughSecuritiesException extends \DomainException
{
    public function __construct(
        public readonly int $available,
    ) {
        parent::__construct(sprintf('Only %d securities can be sold', $available));
    }
}
