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

    public function countByAccount(Account $account): int;

    /**
     * A page of the journal of the account, the latest operations first.
     *
     * @return list<ManualOperation>
     */
    public function findPageByAccount(Account $account, int $offset, int $limit): array;

    /**
     * The operations that opened the lots, or the lots the parts were split off.
     *
     * @return array<string, ManualOperation> by lot; a lot of another account or an unknown one is left out
     */
    public function findOpenings(Account $account, string ...$lots): array;

    public function save(ManualOperation ...$operations): void;

    public function remove(ManualOperation ...$operations): void;
}
