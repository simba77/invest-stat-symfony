<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\AccountIsSyncedException;
use App\Investments\Domain\BrokerSync\BrokerAccountLinkRepositoryInterface;

/**
 * Keeps manual changes away from accounts whose records the broker sync owns.
 */
final readonly class SyncedAccountGuard
{
    public function __construct(
        private BrokerAccountLinkRepositoryInterface $linkRepository,
    ) {
    }

    public function isSynced(Account $account): bool
    {
        return $this->linkRepository->findByAccount($account) !== null;
    }

    /**
     * @throws AccountIsSyncedException
     */
    public function assertManual(?Account ...$accounts): void
    {
        foreach ($accounts as $account) {
            if ($account !== null && $this->isSynced($account)) {
                throw AccountIsSyncedException::forAccount($account);
            }
        }
    }
}
