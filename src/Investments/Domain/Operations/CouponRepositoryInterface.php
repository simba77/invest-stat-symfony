<?php

declare(strict_types=1);

namespace App\Investments\Domain\Operations;

use App\Investments\Domain\Accounts\Account;
use App\Shared\Domain\User;

interface CouponRepositoryInterface
{
    /**
     * @return array<Coupon>
     */
    public function findAll(): array;

    /**
     * @return array<Coupon>
     */
    public function findByUser(?User $user): array;

    /**
     * @return array<Coupon>
     */
    public function getPageByUserId(int $userId, int $offset, int $limit): array;

    public function countByUserId(int $userId): int;

    public function save(Coupon $coupon): void;

    public function remove(Coupon $coupon): void;

    public function findByIdAndUser(int $id, User $user): ?Coupon;

    public function findById(int $id): ?Coupon;

    /**
     * What the coupons brought to the account, in roubles.
     *
     * @return numeric-string
     */
    public function sumByAccount(Account $account): string;

    /**
     * Every record of the account, in the order they were created.
     *
     * @return list<Coupon>
     */
    public function findByAccount(Account $account): array;
}
