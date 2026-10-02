<?php

declare(strict_types=1);

namespace App\Investments\Domain\BrokerSync;

use App\Investments\Domain\Accounts\Account;

interface BrokerAccountLinkRepositoryInterface
{
    public function findByAccount(Account $account): ?BrokerAccountLink;

    public function save(BrokerAccountLink $link): void;

    public function remove(BrokerAccountLink $link): void;
}
