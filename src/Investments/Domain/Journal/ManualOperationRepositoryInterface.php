<?php

declare(strict_types=1);

namespace App\Investments\Domain\Journal;

use App\Investments\Domain\Accounts\Account;

interface ManualOperationRepositoryInterface
{
    /**
     * The journal of the account in the order the operations happened.
     *
     * @return list<ManualOperation>
     */
    public function findByAccount(Account $account): array;

    /**
     * The operation that opened the lot, or a part split off it.
     */
    public function findOpening(Account $account, string $lot): ?ManualOperation;

    /**
     * @return list<ManualOperation> closes, blocks and unblocks of the lot and of the parts split off it
     */
    public function findAboutLot(Account $account, string $lot): array;

    public function save(ManualOperation ...$operations): void;

    public function remove(ManualOperation ...$operations): void;
}
