<?php

declare(strict_types=1);

namespace App\Tests\Investments;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Analytics\Statistic;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Future;
use App\Investments\Domain\Instruments\Securities\ShareTypeEnum;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Deals\DealType;
use App\Shared\Domain\User;

/**
 * A portfolio that touches every branch of the deal calculations: shares in roubles and dollars,
 * long and short, blocked deals, a bond with accrued coupon, a future with a multiplier,
 * closed deals with estimated and with broker commissions, payouts, deposits and statistics.
 *
 * @psalm-require-extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 */
trait PortfolioScenario
{
    use CreatesInvestmentRecords;

    /** @var array<string, Share|Bond|Future> */
    private array $instruments = [];

    /** @var array<string, Deal> */
    private array $deals = [];

    /**
     * @return array{main: Account, second: Account}
     */
    private function createPortfolio(User $owner): array
    {
        // Past rates value past deals and payouts; the latest one values what is held now
        $this->createCurrencyRate('USD', '75', '2023-05-05');
        $this->createCurrencyRate('USD', '100', '2024-11-05');
        $this->createCurrencyRate('USD', '78', '2026-01-20');
        $this->createCurrencyRate('USD', '90', '2026-02-05');
        $this->createCurrencyRate('USD', '80', '2026-03-02');
        $this->createCurrencyRate('CNY', '11', '2026-03-02');

        $sber = new Share('SBER', 'Сбербанк', 'MOEX', 'RUB', '300', ShareTypeEnum::Stock->value, shortName: 'Сбер', lotSize: '10', isin: 'RU0009029540', prevPrice: '290');
        $gazp = new Share('GAZP', 'Газпром', 'MOEX', 'RUB', '150', ShareTypeEnum::Stock->value, shortName: 'Газпром', lotSize: '10', isin: 'RU0007661625', prevPrice: '160');
        $aapl = new Share('AAPL', 'Apple Inc.', 'SPB', 'USD', '200', ShareTypeEnum::Stock->value, shortName: 'Apple', lotSize: '1', isin: 'US0378331005', prevPrice: '190');
        $bond = new Bond('SU26238RMFS4', 'ОФЗ 26238', 'MOEX', 'RUB', '95', prevPrice: '94', shortName: 'ОФЗ 26238', lotSize: '1000', couponAccumulated: '12.5');
        $future = new Future('SiH6', 'Si-3.26', 'MOEX', 'RUB', '90000', prevPrice: '89000', shortName: 'Si-3.26', lotSize: '1', stepPrice: '1');
        $future->setMultiplier('1');
        $this->persist($sber, $gazp, $aapl, $bond, $future);
        $this->instruments = ['SBER' => $sber, 'GAZP' => $gazp, 'AAPL' => $aapl, 'SU26238RMFS4' => $bond, 'SiH6' => $future];

        // Records of another owner stay out of every page. Their account is created first,
        // so that no account of the owner gets id 1, which the dashboard special-cases.
        $stranger = $this->createAccount($this->otherUser(), 'Stranger', '7000');
        $this->deal($stranger, 'SBER', DealType::Long, 7, '100');
        $this->deal($stranger, 'GAZP', DealType::Long, 7, '100', sell: '120', closedAt: '2026-01-05 10:00');
        $this->createDividend($stranger, 'SBER', '70', '2026-01-10');
        $this->createCoupon($stranger, 'SU26238RMFS4', '70', '2026-02-20');
        $this->createInvestment($stranger, '7000', '2025-06-01');

        $main = $this->createAccount($owner, 'Main broker', '50000');
        $main->setUsdBalance('100');
        $main->setCommission('0.3');
        $second = $this->createAccount($owner, 'Second broker', '10000');
        $second->setCommission('0.1');
        $this->persist($main, $second);

        $this->deals = [
            'sber long'         => $this->deal($main, 'SBER', DealType::Long, 10, '250', target: '320', openedAt: '2025-06-10 10:00'),
            'sber long 2'       => $this->deal($main, 'SBER', DealType::Long, 5, '280', openedAt: '2025-09-15 11:30'),
            'sber blocked'      => $this->deal($main, 'SBER', DealType::Long, 4, '200', status: DealStatus::Blocked, openedAt: '2022-01-20 12:00'),
            'gazp short'        => $this->deal($main, 'GAZP', DealType::Short, 20, '170', target: '140', openedAt: '2026-01-12 15:00'),
            'aapl long'         => $this->deal($main, 'AAPL', DealType::Long, 3, '150', target: '220', openedAt: '2024-11-05 18:00'),
            'bond long'         => $this->deal($main, 'SU26238RMFS4', DealType::Long, 2, '98.5', openedAt: '2025-03-03 10:15'),
            'sber closed'       => $this->deal($main, 'SBER', DealType::Long, 10, '200', sell: '260', closedAt: '2025-12-10 14:00', openedAt: '2025-02-01 10:00'),
            'aapl closed'       => $this->deal($main, 'AAPL', DealType::Long, 2, '100', sell: '180', closedAt: '2026-01-20 19:00', openedAt: '2023-05-05 18:00'),
            'future closed'     => $this->deal($main, 'SiH6', DealType::Long, 1, '85000', sell: '88000', closedAt: '2026-02-01 12:00', openedAt: '2026-01-25 12:00'),
            'gazp short closed' => $this->deal($main, 'GAZP', DealType::Short, 10, '180', sell: '160', closedAt: '2026-02-15 16:00', openedAt: '2026-02-02 10:00'),
            'gazp second'       => $this->deal($second, 'GAZP', DealType::Long, 10, '140', openedAt: '2025-07-07 10:00'),
            'sber second'       => $this->deal($second, 'SBER', DealType::Long, 3, '240', sell: '310', closedAt: '2026-01-28 11:00', openedAt: '2025-10-01 10:00', commissions: ['2.16', '2.79']),
        ];

        $this->createDividend($main, 'SBER', '1000', '2026-01-10', tax: '149.43');
        $this->createDividend($main, 'AAPL', '2.5', '2026-02-05', 'SPB', tax: '0.37');
        $this->createCoupon($main, 'SU26238RMFS4', '120', '2026-02-20');
        $this->createInvestment($main, '100000', '2025-06-01');
        $this->createInvestment($main, '-20000', '2026-01-15');
        $this->createInvestment($second, '15000', '2025-09-01');

        $this->persist(
            new Statistic($main, new \DateTimeImmutable('2025-01-02 10:00:00'), '1000', '0', '0', '1000', '1000'),
            new Statistic($main, new \DateTimeImmutable('2026-01-03 10:00:00'), '45000', '100', '100000', '118000', '18000'),
            new Statistic($main, new \DateTimeImmutable('2026-03-01 10:00:00'), '50000', '100', '80000', '121000', '41000'),
            new Statistic($second, new \DateTimeImmutable('2026-01-03 10:00:00'), '10000', '0', '15000', '11500', '-3500'),
            new Statistic($second, new \DateTimeImmutable('2026-03-01 10:00:00'), '10000', '0', '15000', '11600', '-3400'),
        );

        return ['main' => $main, 'second' => $second];
    }

    /**
     * @param numeric-string $buyPrice
     * @param numeric-string|null $sell
     * @param array{numeric-string, numeric-string}|null $commissions what the broker charged for buying and selling
     */
    private function deal(
        Account $account,
        string $ticker,
        DealType $type,
        int $quantity,
        string $buyPrice,
        string $target = '0',
        ?string $sell = null,
        ?string $closedAt = null,
        DealStatus $status = DealStatus::Active,
        string $openedAt = '2025-01-01 10:00',
        ?array $commissions = null,
    ): Deal {
        $instrument = $this->instruments[$ticker];
        $deal = new Deal($this->owner($account), $account, $ticker, $instrument->getStockMarket(), $status, $type, $quantity, $buyPrice, $target);
        if ($sell !== null) {
            $deal->setStatus(DealStatus::Closed);
            $deal->setSellPrice($sell);
            $deal->setClosingDate(new \DateTime($closedAt ?? '2026-01-01 10:00'));
        }
        $deal->setInstrument($instrument);
        if ($commissions !== null) {
            $deal->setCommissions($commissions[0], $commissions[1]);
        }
        $this->persist($deal);
        // The creation date is when the deal was opened
        $deal->wasCreatedAt(new \DateTimeImmutable($openedAt));
        $this->persist($deal);

        return $deal;
    }
}
