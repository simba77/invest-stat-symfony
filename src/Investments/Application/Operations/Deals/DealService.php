<?php

declare(strict_types=1);

namespace App\Investments\Application\Operations\Deals;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Application\Journal\ManualJournal;
use App\Investments\Application\Request\DTO\Operations\SellDealRequestDTO;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Instruments\Bond;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\NotEnoughSecuritiesException;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\Deals\DealStatus;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * Records sales in the journal with the commission of the account tariff; the deals and the cash
 * follow from it.
 */
class DealService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SyncedAccountGuard $syncedAccountGuard,
        private readonly ManualJournal $journal,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Sells the whole deal.
     */
    public function sellOne(Deal $deal, SellDealRequestDTO $dto): void
    {
        $account = $deal->getAccount();
        $this->syncedAccountGuard->assertManual($account);

        $this->journal->record($account, $this->sale($deal, $this->journal->lotOf($deal), $deal->getQuantity(), $dto));
    }

    /**
     * Sells the quantity from the oldest deals of the security that are not blocked.
     *
     * @throws NotEnoughSecuritiesException
     */
    public function sellAsNeeded(Account $account, SellDealRequestDTO $dto): void
    {
        $this->syncedAccountGuard->assertManual($account);
        $deals = $this->entityManager->getRepository(Deal::class)->findBy(
            ['account' => $account, 'ticker' => $dto->ticker, 'status' => DealStatus::Active],
            ['id' => Order::Ascending->value],
        );
        $available = array_sum(array_map(static fn (Deal $deal) => $deal->getQuantity(), $deals));
        if ($deals === [] || $dto->quantity > $available) {
            throw new NotEnoughSecuritiesException($available);
        }

        $this->journal->record($account, $this->sale($deals[0], null, $dto->quantity, $dto));
    }

    private function sale(Deal $deal, ?string $lot, int $quantity, SellDealRequestDTO $dto): ManualOperation
    {
        $instrument = $deal->getInstrument();

        return ManualOperation::close(
            account:         $deal->getAccount(),
            executedAt:      $this->clock->now(),
            instrument:      $instrument,
            ticker:          $deal->getTicker(),
            stockMarket:     (string) $deal->getStockMarket(),
            lot:             $lot,
            quantity:        $quantity,
            price:           $dto->price,
            accruedInterest: $instrument instanceof Bond ? $instrument->getCouponAccumulated() : null,
            commission:      $deal->getAccount()->tradeCommission($instrument, $dto->price, $quantity),
        );
    }
}
