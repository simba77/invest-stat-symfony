<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

use App\Investments\Domain\Accounts\Account;

interface BrokerAccountLinkRepositoryInterface
{
    public function findByAccount(Account $account): ?BrokerAccountLink;

    /**
     * @return list<BrokerAccountLink>
     */
    public function findEnabled(): array;

    public function save(BrokerAccountLink $link): void;

    public function remove(BrokerAccountLink $link): void;
}
