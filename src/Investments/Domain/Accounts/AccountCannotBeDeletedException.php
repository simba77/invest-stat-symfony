<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

final class AccountCannotBeDeletedException extends \DomainException
{
    public static function hasRecords(Account $account): self
    {
        return new self(sprintf(
            'Account "%s" has deals, deposits or payouts. Only an empty account can be deleted; close it instead.',
            (string) $account->getName(),
        ));
    }
}
