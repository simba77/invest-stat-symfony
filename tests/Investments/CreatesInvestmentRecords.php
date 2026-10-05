<?php

declare(strict_types=1);

namespace App\Tests\Investments;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\CurrencyRate;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\Investment;
use App\Shared\Domain\User;

/**
 * @psalm-require-extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
trait CreatesInvestmentRecords
{
    /**
     * @param numeric-string $balance
     */
    private function createAccount(User $owner, string $name = 'Broker', string $balance = '0'): Account
    {
        $account = new Account($owner, $name, balance: $balance);
        $this->persist($account);

        return $account;
    }

    private function createShare(string $ticker, string $stockMarket = 'MOEX', string $currency = 'RUB', string $price = '100'): Share
    {
        $share = new Share($ticker, $ticker, $stockMarket, $currency, $price, ShareTypeEnum::Stock->value, shortName: $ticker);
        $this->persist($share);

        return $share;
    }

    private function createBond(string $ticker, string $currency = 'RUB', string $price = '100', string $nominal = '1000'): Bond
    {
        $bond = new Bond($ticker, $ticker, 'MOEX', $currency, $price, prevPrice: $price, shortName: $ticker, lotSize: $nominal);
        $this->persist($bond);

        return $bond;
    }

    private function createCurrencyRate(string $currency, string $rate, string $date = '2026-01-01'): void
    {
        $this->persist(new CurrencyRate('RUB', $currency, $rate, new \DateTimeImmutable($date)));
    }

    private function createDividend(
        Account $account,
        string $ticker = 'SBER',
        string $amount = '100',
        string $date = '2026-01-15',
        string $stockMarket = 'MOEX',
        string $tax = '0',
    ): Dividend {
        $dividend = new Dividend($account, $ticker, $stockMarket, $amount, $tax, new \DateTimeImmutable($date));
        // Linked to the share like a dividend entered by hand, when the catalogue has it
        $dividend->setShare($this->entityManager()->getRepository(Share::class)->findOneBy(['ticker' => $ticker, 'stockMarket' => $stockMarket]));
        $this->persist($dividend);

        return $dividend;
    }

    private function createCoupon(Account $account, string $ticker = 'SU26238RMFS4', string $amount = '100', string $date = '2026-01-15'): Coupon
    {
        $coupon = new Coupon($account, $ticker, 'MOEX', $amount, new \DateTimeImmutable($date));
        $coupon->setBond($this->entityManager()->getRepository(Bond::class)->findOneBy(['ticker' => $ticker, 'stockMarket' => 'MOEX']));
        $this->persist($coupon);

        return $coupon;
    }

    private function createInvestment(Account $account, string $sum = '1000', string $date = '2026-01-15'): Investment
    {
        $investment = new Investment($sum, new \DateTimeImmutable($date), $account);
        $this->persist($investment);

        return $investment;
    }
}
