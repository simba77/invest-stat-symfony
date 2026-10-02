<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

use App\Investments\Domain\Accounts\Account;

/**
 * Records of a synced account are rebuilt from the broker, so changes by hand would be lost.
 */
final class AccountIsSyncedException extends \RuntimeException
{
    public static function forAccount(Account $account): self
    {
        return new self(sprintf(
            'Account "%s" is synced with a broker: its deals, deposits and payouts come from the broker. Unlink it to edit them by hand.',
            (string) $account->getName(),
        ));
    }
}
