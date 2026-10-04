<?php

declare(strict_types=1);

namespace App\Investments\Application\BrokerSync;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\Client\ExternalInstrument;
use App\Investments\Domain\BrokerSync\Ledger\InstrumentRef;
use App\Investments\Domain\BrokerSync\Ledger\Ledger;
use App\Investments\Domain\BrokerSync\Ledger\Lot;
use App\Investments\Domain\BrokerSync\Ledger\Payout;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Instruments\Share;
use App\Investments\Domain\Operations\Coupon;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\Dividend;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Investments\Domain\Operations\Investment;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use App\Shared\Domain\User;
use App\Shared\Domain\UserRepositoryInterface;
use App\Shared\Infrastructure\Symfony\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Writes a replayed ledger into the deals, dividends, coupons and deposits of the account.
 * Records are matched by the broker operation they come from, so their ids survive syncs;
 * records the ledger no longer has are removed, and so are the ones entered by hand.
 */
final class LedgerProjector
{
    /** @var array<string, Share|Bond|null> resolved instruments by broker uid */
    private array $instruments = [];

    /** @var list<string> */
    private array $warnings = [];

    public function __construct(
        private readonly DealRepositoryInterface $dealRepository,
        private readonly DividendRepositoryInterface $dividendRepository,
        private readonly CouponRepositoryInterface $couponRepository,
        private readonly InvestmentRepositoryInterface $investmentRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly InstrumentResolver $instrumentResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param \Closure(string): ?ExternalInstrument $lookup asks the broker about an unknown instrument
     * @return list<string> warnings
     */
    public function project(Account $account, Ledger $ledger, \Closure $lookup): array
    {
        $this->instruments = [];
        $this->warnings = [];
        $user = $this->userRepository->findById($account->getUserId() ?? 0)
            ?? throw new NotFoundException(sprintf('Owner of account "%s" not found', (string) $account->getId()));

        $openedDeals = $this->projectDeals($account, $user, $ledger->lots, $lookup);
        $this->projectDividends($account, $user, $ledger->dividends, $lookup);
        $this->projectCoupons($account, $user, $ledger->coupons, $lookup);
        $this->projectCashFlows($account, $ledger);
        $this->entityManager->flush();

        // New records get the current time as their creation date; a deal is created when it was opened
        foreach ($openedDeals as [$deal, $openedAt]) {
            $deal->wasCreatedAt($openedAt);
        }
        $this->entityManager->flush();

        return $this->warnings;
    }

    /**
     * @param list<Lot> $lots
     * @param \Closure(string): ?ExternalInstrument $lookup
     * @return list<array{Deal, \DateTimeImmutable}> the deals created by this run, with their opening time
     */
    private function projectDeals(Account $account, User $user, array $lots, \Closure $lookup): array
    {
        $synced = [];
        $targetPrices = [];
        foreach ($this->dealRepository->findByAccount($account) as $deal) {
            $externalId = $deal->getExternalId();
            if (! $deal->isSynced() || $externalId === null) {
                $this->entityManager->remove($deal);
                continue;
            }
            $synced[$externalId] = $deal;
            $targetPrice = $deal->getTargetPrice();
            if ($deal->getStatus() === DealStatus::Active && $targetPrice !== null && bccomp($targetPrice, '0', 4) !== 0) {
                $targetPrices[$deal->getTicker()] = $targetPrice;
            }
        }

        $lastPrices = $this->lastTradePrices($lots);
        $created = [];
        foreach ($lots as $lot) {
            $instrument = $this->instrument($lot->getInstrument(), $lookup);
            if ($instrument === null) {
                continue;
            }
            $this->priceUnquoted($instrument, $lastPrices[$lot->getInstrument()->uid] ?? null);

            $deal = $synced[$lot->getKey()] ?? null;
            unset($synced[$lot->getKey()]);
            $buyPrice = $this->price($lot->getOpenPrice(), $instrument);
            if ($deal === null) {
                $deal = new Deal(
                    user:        $user,
                    account:     $account,
                    ticker:      $instrument->getTicker(),
                    stockMarket: $instrument->getStockMarket(),
                    status:      DealStatus::Active,
                    type:        $lot->getDirection(),
                    quantity:    $lot->getQuantity(),
                    buyPrice:    $buyPrice,
                    targetPrice: $lot->isOpen() ? ($targetPrices[$instrument->getTicker()] ?? '0') : '0',
                );
                $deal->markSynced($lot->getKey());
                $this->entityManager->persist($deal);
                $created[] = [$deal, $lot->getOpenedAt()];
            }

            $this->updateDeal($deal, $lot, $instrument, $buyPrice);
        }

        foreach ($synced as $deal) {
            $this->entityManager->remove($deal);
        }

        return $created;
    }

    /**
     * @param numeric-string $buyPrice
     */
    private function updateDeal(Deal $deal, Lot $lot, Share|Bond $instrument, string $buyPrice): void
    {
        $closePrice = $lot->getClosePrice();
        $deal->setTicker($instrument->getTicker());
        $deal->setStockMarket($instrument->getStockMarket());
        $deal->setType($lot->getDirection());
        $deal->setQuantity($lot->getQuantity());
        $deal->setBuyPrice($buyPrice);
        $deal->setStatus($lot->isOpen() ? DealStatus::Active : DealStatus::Closed);
        // Stored with four decimals: the same string avoids an update on every sync
        $deal->setSellPrice($closePrice !== null ? $this->price($closePrice, $instrument) : '0.0000');
        if (! self::sameMoment($deal->getClosingDate(), $lot->getClosedAt())) {
            $deal->setClosingDate($lot->getClosedAt());
        }
        $deal->setInstrument($instrument);
        $deal->setCommissions(
            bcadd($lot->getOpenCommission(), '0', 4),
            $lot->isOpen() ? null : bcadd($lot->getCloseCommission(), '0', 4),
        );
    }

    /**
     * The price of the latest trade of every instrument.
     *
     * @param list<Lot> $lots
     * @return array<string, numeric-string> by broker uid
     */
    private function lastTradePrices(array $lots): array
    {
        $latest = [];
        foreach ($lots as $lot) {
            $uid = $lot->getInstrument()->uid;
            $events = [[$lot->getOpenedAt(), $lot->getOpenPrice()]];
            $closedAt = $lot->getClosedAt();
            $closePrice = $lot->getClosePrice();
            if ($closedAt !== null && $closePrice !== null) {
                $events[] = [$closedAt, $closePrice];
            }
            foreach ($events as [$at, $price]) {
                if (! isset($latest[$uid]) || $latest[$uid][0] < $at) {
                    $latest[$uid] = [$at, $price];
                }
            }
        }

        return array_map(static fn (array $event): string => $event[1], $latest);
    }

    /**
     * An instrument the sync has just added has no quote until prices are updated;
     * until then it is valued at its last trade, not at zero.
     *
     * @param numeric-string|null $lastPrice
     */
    private function priceUnquoted(Share|Bond $instrument, ?string $lastPrice): void
    {
        if ($lastPrice === null || ! is_numeric($instrument->getPrice()) || bccomp($instrument->getPrice(), '0', 4) !== 0) {
            return;
        }

        $price = $this->price($lastPrice, $instrument);
        $instrument->setPrice($price);
        $instrument->setPrevPrice($price);
    }

    /**
     * @param list<Payout> $payouts
     * @param \Closure(string): ?ExternalInstrument $lookup
     */
    private function projectDividends(Account $account, User $user, array $payouts, \Closure $lookup): void
    {
        $synced = [];
        foreach ($this->dividendRepository->findByAccount($account) as $dividend) {
            $externalId = $dividend->getExternalId();
            if (! $dividend->isSynced() || $externalId === null) {
                $this->entityManager->remove($dividend);
                continue;
            }
            $synced[$externalId] = $dividend;
        }

        foreach ($payouts as $payout) {
            [$ticker, $stockMarket, $instrument] = $this->tickerAndMarket($payout->getInstrument(), $lookup);
            $date = Ledger::localDate($payout->getPaidAt());
            $dividend = $synced[$payout->getExternalId()] ?? null;
            unset($synced[$payout->getExternalId()]);
            if ($dividend === null) {
                $dividend = new Dividend($user, $account, $ticker, $stockMarket, bcadd($payout->getNet(), '0', 4), bcadd($payout->getTax(), '0', 4), $date);
                $dividend->setShare($instrument instanceof Share ? $instrument : null);
                $dividend->markSynced($payout->getExternalId());
                $this->entityManager->persist($dividend);
                continue;
            }

            $dividend->setShare($instrument instanceof Share ? $instrument : null);
            $dividend->setTicker($ticker);
            $dividend->setStockMarket($stockMarket);
            $dividend->setAmount(bcadd($payout->getNet(), '0', 4));
            $dividend->setTax(bcadd($payout->getTax(), '0', 4));
            if (! self::sameMoment($dividend->getDate(), $date)) {
                $dividend->setDate($date);
            }
        }

        foreach ($synced as $dividend) {
            $this->entityManager->remove($dividend);
        }
    }

    /**
     * @param list<Payout> $payouts
     * @param \Closure(string): ?ExternalInstrument $lookup
     */
    private function projectCoupons(Account $account, User $user, array $payouts, \Closure $lookup): void
    {
        $synced = [];
        foreach ($this->couponRepository->findByAccount($account) as $coupon) {
            $externalId = $coupon->getExternalId();
            if (! $coupon->isSynced() || $externalId === null) {
                $this->entityManager->remove($coupon);
                continue;
            }
            $synced[$externalId] = $coupon;
        }

        foreach ($payouts as $payout) {
            [$ticker, $stockMarket, $instrument] = $this->tickerAndMarket($payout->getInstrument(), $lookup);
            $date = Ledger::localDate($payout->getPaidAt());
            $coupon = $synced[$payout->getExternalId()] ?? null;
            unset($synced[$payout->getExternalId()]);
            if ($coupon === null) {
                $coupon = new Coupon($user, $account, $ticker, $stockMarket, bcadd($payout->getNet(), '0', 4), $date);
                $coupon->setBond($instrument instanceof Bond ? $instrument : null);
                $coupon->markSynced($payout->getExternalId());
                $this->entityManager->persist($coupon);
                continue;
            }

            $coupon->setBond($instrument instanceof Bond ? $instrument : null);
            $coupon->setTicker($ticker);
            $coupon->setStockMarket($stockMarket);
            $coupon->setAmount(bcadd($payout->getNet(), '0', 4));
            if (! self::sameMoment($coupon->getDate(), $date)) {
                $coupon->setDate($date);
            }
        }

        foreach ($synced as $coupon) {
            $this->entityManager->remove($coupon);
        }
    }

    private function projectCashFlows(Account $account, Ledger $ledger): void
    {
        $synced = [];
        foreach ($this->investmentRepository->findByAccount($account) as $investment) {
            $externalId = $investment->getExternalId();
            if (! $investment->isSynced() || $externalId === null) {
                $this->entityManager->remove($investment);
                continue;
            }
            $synced[$externalId] = $investment;
        }

        foreach ($ledger->cashFlows as $cashFlow) {
            if ($cashFlow->currency !== 'RUB') {
                $this->warnings[] = sprintf('Deposit %s in %s is not counted: deposits are kept in roubles', $cashFlow->externalId, $cashFlow->currency);
                continue;
            }

            $sum = bcadd($cashFlow->amount, '0', 2);
            $date = Ledger::localDate($cashFlow->at);
            $investment = $synced[$cashFlow->externalId] ?? null;
            unset($synced[$cashFlow->externalId]);
            if ($investment === null) {
                $investment = new Investment($sum, $date, $account, (int) $account->getUserId());
                $investment->markSynced($cashFlow->externalId);
                $this->entityManager->persist($investment);
                continue;
            }

            $investment->setSum($sum);
            if (! self::sameMoment($investment->getDate(), $date)) {
                $investment->setDate($date);
            }
        }

        foreach ($synced as $investment) {
            $this->entityManager->remove($investment);
        }
    }

    /**
     * @param \Closure(string): ?ExternalInstrument $lookup
     */
    private function instrument(InstrumentRef $instrument, \Closure $lookup): Share|Bond|null
    {
        if (! array_key_exists($instrument->uid, $this->instruments)) {
            $resolved = $this->instrumentResolver->resolve($instrument, $lookup);
            if ($resolved === null) {
                $this->warnings[] = sprintf('%s: the instrument is unknown to the catalogue and the broker, its deals are skipped', $instrument->ticker);
            }
            $this->instruments[$instrument->uid] = $resolved;
        }

        return $this->instruments[$instrument->uid];
    }

    /**
     * @param \Closure(string): ?ExternalInstrument $lookup
     * @return array{string, string, Share|Bond|null}
     */
    private function tickerAndMarket(InstrumentRef $instrument, \Closure $lookup): array
    {
        $resolved = $this->instrument($instrument, $lookup);

        return $resolved !== null
            ? [$resolved->getTicker(), $resolved->getStockMarket(), $resolved]
            : [$instrument->ticker, $instrument->stockMarket, null];
    }

    /**
     * Deals keep bond prices in percent of the nominal, as the exchange quotes them.
     *
     * @param numeric-string $price per security
     * @return numeric-string
     */
    private function price(string $price, Share|Bond $instrument): string
    {
        if ($instrument instanceof Share) {
            return bcadd($price, '0', 4);
        }

        $nominal = $instrument->getLotSize();
        if ($nominal === null || ! is_numeric($nominal) || bccomp($nominal, '0', 9) <= 0) {
            $this->warnings[] = sprintf('%s: the bond has no nominal, its price is kept in money', $instrument->getTicker());

            return bcadd($price, '0', 4);
        }

        return bcdiv(bcmul($price, '100', 9), $nominal, 4);
    }

    private static function sameMoment(?\DateTimeInterface $current, ?\DateTimeInterface $new): bool
    {
        if ($current === null || $new === null) {
            return $current === $new;
        }

        return $current->format('Y-m-d H:i:s') === $new->format('Y-m-d H:i:s');
    }
}
