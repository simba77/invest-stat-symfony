<?php

declare(strict_types=1);

namespace App\Investments\Application\Journal;

use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\BrokerSync\Ledger\Lot;
use App\Investments\Domain\Instruments\Instrument;
use App\Investments\Domain\Journal\ManualLedger;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Writes the lots of a manual journal into the deals of the account. A deal is the lot with the
 * same key, so its id survives rebuilds; a deal without a lot is removed.
 */
final readonly class ManualLedgerProjector
{
    public function __construct(
        private DealRepositoryInterface $dealRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function project(Account $account, ManualLedger $ledger): void
    {
        $deals = [];
        foreach ($this->dealRepository->findByAccount($account) as $deal) {
            $key = $deal->getExternalId();
            if ($key === null) {
                $this->entityManager->remove($deal);
                continue;
            }
            $deals[$key] = $deal;
        }

        $created = [];
        foreach ($ledger->lots as $lot) {
            $instrument = $ledger->instruments[$lot->getInstrument()->uid] ?? null;
            $deal = $deals[$lot->getKey()] ?? null;
            unset($deals[$lot->getKey()]);
            if ($deal === null) {
                $deal = new Deal(
                    account:     $account,
                    ticker:      $lot->getInstrument()->ticker,
                    stockMarket: $lot->getInstrument()->stockMarket,
                    status:      DealStatus::Active,
                    type:        $lot->getDirection(),
                    quantity:    $lot->getQuantity(),
                    buyPrice:    bcadd($lot->getOpenPrice(), '0', 4),
                );
                $deal->trackAs($lot->getKey());
                $this->entityManager->persist($deal);
                $created[] = [$deal, $lot->getOpenedAt()];
            }
            $this->update($deal, $lot, $instrument, isset($ledger->knownCommissions[$lot->getOrigin()]));
        }

        foreach ($deals as $deal) {
            $this->entityManager->remove($deal);
        }
        $this->entityManager->flush();

        // New records get the current time as their creation date; a deal is created when it was opened
        foreach ($created as [$deal, $openedAt]) {
            $deal->wasCreatedAt($openedAt);
        }
        $this->entityManager->flush();
    }

    /**
     * Sets only what differs, so that a rebuild does not touch deals that did not change.
     */
    private function update(Deal $deal, Lot $lot, ?Instrument $instrument, bool $commissionKnown): void
    {
        if ($deal->getTicker() !== $lot->getInstrument()->ticker) {
            $deal->setTicker($lot->getInstrument()->ticker);
        }
        if ($deal->getStockMarket() !== $lot->getInstrument()->stockMarket) {
            $deal->setStockMarket($lot->getInstrument()->stockMarket);
        }
        if ($deal->getInstrument()?->getId() !== $instrument?->getId()) {
            $deal->setInstrument($instrument);
        }
        if ($deal->getType() !== $lot->getDirection()) {
            $deal->setType($lot->getDirection());
        }
        if ($deal->getQuantity() !== $lot->getQuantity()) {
            $deal->setQuantity($lot->getQuantity());
        }
        if (self::differs($deal->getBuyPrice(), $lot->getOpenPrice())) {
            $deal->setBuyPrice(bcadd($lot->getOpenPrice(), '0', 4));
        }
        if (self::differs($deal->getTargetPrice(), $lot->getTarget())) {
            $deal->setTargetPrice($lot->getTarget() !== null ? bcadd($lot->getTarget(), '0', 4) : null);
        }

        $status = match (true) {
            ! $lot->isOpen() => DealStatus::Closed,
            $lot->isBlocked() => DealStatus::Blocked,
            default => DealStatus::Active,
        };
        if ($deal->getStatus() !== $status) {
            $deal->setStatus($status);
        }
        if (self::differs($deal->getSellPrice(), $lot->getClosePrice())) {
            $deal->setSellPrice(bcadd($lot->getClosePrice() ?? '0', '0', 4));
        }
        $closedAt = $lot->getClosedAt();
        if ($deal->getClosingDate()?->format('Y-m-d H:i:s') !== $closedAt?->format('Y-m-d H:i:s')) {
            $deal->setClosingDate($closedAt !== null ? \DateTime::createFromImmutable($closedAt) : null);
        }

        $buyCommission = $commissionKnown ? bcadd($lot->getOpenCommission(), '0', 4) : null;
        $sellCommission = $commissionKnown && ! $lot->isOpen() ? bcadd($lot->getCloseCommission(), '0', 4) : null;
        if (self::differs($deal->getBuyCommission(), $buyCommission, true) || self::differs($deal->getSellCommission(), $sellCommission, true)) {
            $deal->setCommissions($buyCommission, $sellCommission);
        }
    }

    /**
     * Prices compare as numbers; an absent one counts as zero unless null matters.
     */
    private static function differs(?string $current, ?string $new, bool $nullMatters = false): bool
    {
        if ($nullMatters && ($current === null || $new === null)) {
            return $current !== $new;
        }

        return bccomp($current ?? '0', $new ?? '0', 4) !== 0;
    }
}
