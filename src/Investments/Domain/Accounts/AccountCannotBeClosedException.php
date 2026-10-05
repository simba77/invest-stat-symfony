<?php

declare(strict_types=1);

namespace App\Investments\Domain\Accounts;

final class AccountCannotBeClosedException extends \DomainException
{
    public static function synced(Account $account): self
    {
        return new self(sprintf(
            'Account "%s" is synced with a broker. Unlink it before closing.',
            (string) $account->getName(),
        ));
    }
}
