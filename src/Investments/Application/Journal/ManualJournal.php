<?php

declare(strict_types=1);

namespace App\Investments\Application\Journal;

use App\Investments\Application\BrokerSync\SyncedAccountGuard;
use App\Investments\Domain\Accounts\Account;
use App\Investments\Domain\Accounts\AccountRepositoryInterface;
use App\Investments\Domain\Journal\ManualLedger;
use App\Investments\Domain\Journal\ManualLedgerReplayer;
use App\Investments\Domain\Journal\ManualOperation;
use App\Investments\Domain\Journal\ManualOperationRepositoryInterface;
use App\Investments\Domain\Journal\ManualOperationType;
use App\Investments\Domain\Operations\CouponRepositoryInterface;
use App\Investments\Domain\Operations\Deal;
use App\Investments\Domain\Operations\DealRepositoryInterface;
use App\Investments\Domain\Operations\Deals\DealStatus;
use App\Investments\Domain\Operations\DividendRepositoryInterface;
use App\Investments\Domain\Operations\InvestmentRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * The journal of a manual account: the deals and the cash of the account are rebuilt from it after
 * every change. Cash is what the trades, the deposits and the payouts brought, plus adjustments;
 * blocks of cash set aside the part of it that cannot be used.
 *
 * An account that has no journal yet starts one from its deals as they are, and an adjustment
 * keeps its cash as it was.
 */
final readonly class ManualJournal
{
    public function __construct(
        private ManualOperationRepositoryInterface $operationRepository,
        private DealRepositoryInterface $dealRepository,
        private InvestmentRepositoryInterface $investmentRepository,
        private DividendRepositoryInterface $dividendRepository,
        private CouponRepositoryInterface $couponRepository,
        private AccountRepositoryInterface $accountRepository,
        private ManualLedgerProjector $projector,
        private SyncedAccountGuard $syncedAccountGuard,
        private EntityManagerInterface $entityManager,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private ManualLedgerReplayer $replayer = new ManualLedgerReplayer(),
    ) {
    }

    public function record(Account $account, ManualOperation ...$operations): void
    {
        $this->entityManager->wrapInTransaction(function () use ($account, $operations): void {
            $this->start($account);
            $this->operationRepository->save(...$operations);
            $this->rebuildStarted($account);
        });
    }

    /**
     * Applies a change of payouts or deposits and rebuilds the cash of the accounts it touches.
     * The journals start before the change, so that their cash adjustment does not cancel it.
     *
     * @param \Closure(): void $change
     */
    public function changeRecords(\Closure $change, ?Account ...$accounts): void
    {
        $accounts = array_values(array_filter($accounts));
        $this->entityManager->wrapInTransaction(function () use ($change, $accounts): void {
            foreach ($accounts as $account) {
                $this->start($account);
            }
            $change();
            foreach (array_unique($accounts, SORT_REGULAR) as $account) {
                $this->rebuildStarted($account);
            }
        });
    }

    /**
     * Brings the cash in the currency to the amount by recording the difference.
     *
     * @param numeric-string $amount
     */
    public function adjustCash(Account $account, string $currency, string $amount): void
    {
        $this->start($account);
        $difference = bcsub($amount, $account->getCash($currency), 4);
        if (bccomp($difference, '0', 4) !== 0) {
            $this->record($account, ManualOperation::cashAdjustment($account, $this->clock->now(), $currency, $difference));
        }
    }

    /**
     * Blocks or frees cash so that the given part of the cash in the currency is blocked.
     *
     * @param numeric-string $amount
     */
    public function adjustBlockedCash(Account $account, string $currency, string $amount): void
    {
        $this->start($account);
        $difference = bcsub($amount, $account->getBlockedCash($currency), 4);
        if (bccomp($difference, '0', 4) !== 0) {
            $this->record($account, ManualOperation::blockCash($account, $this->clock->now(), $currency, $difference));
        }
    }

    /**
     * The key of the lot of a deal, by which the journal refers to it.
     */
    public function lotOf(Deal $deal): string
    {
        $this->start($deal->getAccount());

        return $deal->getExternalId() ?? throw new \LogicException(sprintf('Deal %s is not in the journal', (string) $deal->getId()));
    }

    /**
     * Rebuilds the deals and the cash of a manual account; a synced account is left to the broker sync.
     */
    public function rebuild(Account $account): void
    {
        $this->entityManager->wrapInTransaction(function () use ($account): void {
            $this->start($account);
            $this->rebuildStarted($account);
        });
    }

    /**
     * Records the deals of the account as they are and keeps its cash with an adjustment.
     */
    public function start(Account $account): void
    {
        if ($account->getJournalStartedAt() !== null || $this->syncedAccountGuard->isSynced($account)) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($account): void {
            $now = $this->clock->now();
            // Operations left from before the account was synced no longer describe it
            $this->operationRepository->remove(...$this->operationRepository->findByAccount($account));

            $deals = $this->dealRepository->findByAccount($account);
            $openings = array_map(fn (Deal $deal) => $this->opening($deal), $deals);
            $this->operationRepository->save(...$openings);

            $operations = [];
            foreach ($deals as $index => $deal) {
                $lot = $openings[$index]->getOpenedLot();
                $deal->trackAs($lot);
                array_push($operations, ...$this->afterOpening($deal, $lot));
            }
            $this->operationRepository->save(...$operations);

            $computed = $this->cash($account, $this->replay($account));
            $adjustments = [];
            foreach (array_unique([...array_keys($account->getCashByCurrency()), ...array_keys($computed)]) as $currency) {
                $difference = bcsub($account->getCash($currency), $computed[$currency] ?? '0', 4);
                if (bccomp($difference, '0', 4) !== 0) {
                    $adjustments[] = ManualOperation::cashAdjustment($account, $now, $currency, $difference);
                }
                if (bccomp($account->getBlockedCash($currency), '0', 4) !== 0) {
                    $adjustments[] = ManualOperation::blockCash($account, $now, $currency, $account->getBlockedCash($currency));
                }
            }
            $this->operationRepository->save(...$adjustments);

            $account->startJournal($now);
            $this->accountRepository->save($account);
        });
    }

    private function rebuildStarted(Account $account): void
    {
        if ($account->getJournalStartedAt() === null) {
            return;
        }

        $ledger = $this->replay($account);
        $this->projector->project($account, $ledger);
        $cash = $this->cash($account, $ledger);
        foreach (array_unique([...array_keys($account->getCashByCurrency()), ...array_keys($cash), ...array_keys($ledger->blockedCash)]) as $currency) {
            $account->setCash($currency, $cash[$currency] ?? '0');
            $account->setBlockedCash($currency, $ledger->blockedCash[$currency] ?? '0');
        }
        $this->accountRepository->save($account);
    }

    private function replay(Account $account): ManualLedger
    {
        $ledger = $this->replayer->replay($this->operationRepository->findByAccount($account));
        foreach ($ledger->warnings as $warning) {
            $this->logger->warning('Journal of account {account}: {warning}', ['account' => $account->getId(), 'warning' => $warning]);
        }

        return $ledger;
    }

    /**
     * @return array<string, numeric-string> by currency
     */
    private function cash(Account $account, ManualLedger $ledger): array
    {
        $cash = $ledger->cash;
        $add = static function (string $currency, string $amount) use (&$cash): void {
            $cash[$currency] = bcadd($cash[$currency] ?? '0', $amount, 4);
        };
        $add('RUB', $this->investmentRepository->sumByAccount($account));
        $add('RUB', $this->couponRepository->sumByAccount($account));
        foreach ($this->dividendRepository->sumByAccountAndCurrency($account) as $currency => $amount) {
            $add($currency, $amount);
        }

        return $cash;
    }

    private function opening(Deal $deal): ManualOperation
    {
        return ManualOperation::open(
            account:     $deal->getAccount(),
            type:        ManualOperationType::opening($deal->getType()),
            executedAt:  $deal->createdAt(),
            instrument:  $deal->getInstrument(),
            ticker:      $deal->getTicker(),
            stockMarket: (string) $deal->getStockMarket(),
            quantity:    $deal->getQuantity(),
            price:       $deal->getBuyPrice(),
            targetPrice: $deal->getTargetPrice(),
            commission:  $deal->getBuyCommission(),
        );
    }

    /**
     * @return list<ManualOperation>
     */
    private function afterOpening(Deal $deal, string $lot): array
    {
        if ($deal->getStatus() === DealStatus::Blocked) {
            return [ManualOperation::block($deal->getAccount(), $deal->createdAt(), $lot)];
        }
        if ($deal->getStatus() !== DealStatus::Closed) {
            return [];
        }

        $closedAt = $deal->getClosingDate();

        return [ManualOperation::close(
            account:     $deal->getAccount(),
            executedAt:  $closedAt !== null ? \DateTimeImmutable::createFromInterface($closedAt) : $deal->createdAt(),
            instrument:  $deal->getInstrument(),
            ticker:      $deal->getTicker(),
            stockMarket: (string) $deal->getStockMarket(),
            lot:         $lot,
            quantity:    $deal->getQuantity(),
            price:       $deal->getSellPrice() ?? '0',
            commission:  $deal->getSellCommission(),
        )];
    }
}
