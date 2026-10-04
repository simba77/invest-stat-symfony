<?php

declare(strict_types=1);

namespace App\Tests\Investments\Application\Controller;

use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Accounts\Account;
use App\Tests\Investments\CreatesInvestmentRecords;
use App\Tests\Support\ApiTestCase;

final class CouponsControllerTest extends ApiTestCase
{
    use CreatesInvestmentRecords;

    public function testCreateLeavesCashUntouched(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin, balance: '1000');
        $bond = $this->createBond('SU26238RMFS4');
        $this->loginAs($admin);

        $this->postJson('/api/coupons/create', [
            'accountId'   => $account->getId(),
            'amount'      => '120',
            'ticker'      => 'SU26238RMFS4',
            'stockMarket' => 'MOEX',
            'date'        => '2026-02-20',
        ]);

        self::assertResponseStatusCodeSame(201);
        $coupons = $this->findFreshBy(Coupon::class, ['account' => $account->getId()]);
        self::assertSame(['120.0000', '2026-02-20'], [$coupons[0]->getAmount(), $coupons[0]->getDate()?->format('Y-m-d')]);
        self::assertSame($bond->getId(), $coupons[0]->getBond()?->getId());
        self::assertSame('1000.0000', $this->findFresh(Account::class, $account->getId())?->getBalance());
    }

    public function testCreateRejectsOtherUsersAccount(): void
    {
        $account = $this->createAccount($this->otherUser());
        $this->loginAs($this->admin());

        $this->postJson('/api/coupons/create', [
            'accountId'   => $account->getId(),
            'amount'      => '100',
            'ticker'      => 'SU26238RMFS4',
            'stockMarket' => 'MOEX',
            'date'        => '2026-01-15',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([], $this->findFreshBy(Coupon::class, ['account' => $account->getId()]));
    }

    public function testEditDoesNotMoveCouponToOtherUsersAccount(): void
    {
        $admin = $this->admin();
        $account = $this->createAccount($admin);
        $coupon = $this->createCoupon($account, amount: '100');
        $othersAccount = $this->createAccount($this->otherUser());
        $this->loginAs($admin);

        $this->postJson('/api/coupons/update/' . $coupon->getId(), [
            'accountId'   => $othersAccount->getId(),
            'amount'      => '200',
            'ticker'      => 'SU26238RMFS4',
            'stockMarket' => 'MOEX',
            'date'        => '2026-01-15',
        ]);

        self::assertResponseStatusCodeSame(404);
        $coupon = $this->findFresh(Coupon::class, $coupon->getId());
        self::assertSame($account->getId(), $coupon?->getAccount()?->getId());
        self::assertSame('100.0000', $coupon?->getAmount());
    }
}
