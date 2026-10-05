# Architecture

## Bounded contexts

`src/` is split into contexts, each with the same layers:

* `Shared` — user, auth, buses, pagination, common infrastructure
* `Investments` — accounts, deals, instruments, dividends, coupons, analytics, tax
* `Deposits` — deposit accounts and deposits
* `Expenses` — expense categories and expenses

Layers inside a context:

* `Domain` — Doctrine entities (attribute mapping), enums, `*RepositoryInterface`, pure services.
* `Application` — controllers, request/response DTOs, compilers, use cases, commands/queries and their handlers, console commands.
* `Infrastructure` — repository implementations (`Infrastructure/Persistence/Repository`), HTTP clients for external APIs (`Infrastructure/Http`), Symfony/Doctrine adapters.

## Dependency direction

* `Application → Domain`; `Infrastructure` implements `Domain` contracts.
* `Domain` does not depend on `Application` or `Infrastructure`.
* Controllers and use cases get repositories and providers as interfaces, never via
  `EntityManager->getRepository()`. Existing code that still does it is legacy — do not copy it.
* Contexts may use `Shared`; avoid new cross-dependencies between other contexts.

## Ownership

Accounts belong to a user (`accounts.user_id`); deals, dividends, coupons, deposits and journal
operations belong to an account and reach their owner through it. Scope lookups by the account's
owner (`getByIdAndUser()`, `findByIdAndUser()`), never by an id alone.

An account the user no longer uses is closed (`accounts.closed_at`), not deleted: it moves to the
collapsed "Closed accounts" block and out of the account selects of the forms, while its records
stay in the analytics and the statistics. A synced account is unlinked before closing. Only an
account without deals, deposits, dividends and coupons can be deleted (HTTP 409 otherwise); its
statistics, cash and journal go with it.

## Repositories

* Contract: `Domain/*RepositoryInterface`.
* Implementation: `Infrastructure/Persistence/Repository/*Repository`, extending
  `App\Shared\Infrastructure\Persistence\Doctrine\ServiceEntityRepository`.
* Autowiring binds a single implementation automatically; explicit bindings live in
  `config/services.yaml` and `config/services/doctrine.php`.

## Commands and queries

Handled by Symfony Messenger, dispatched synchronously:

* Command: `*Command` DTO + `*CommandHandler` with `#[AsMessageHandler]` and `__invoke()`,
  dispatched via `App\Shared\Domain\Bus\SyncCommandBusInterface`.
* Query: `*Query implements QueryInterface<TResult>` + `*QueryHandler`, executed via
  `QueryBusInterface::ask()` which returns the handler's result.
* Page read models without a bus: `Application/UseCases/*UseCase` with an `execute()` method.

Note: `Application/Command/` holds Symfony **console** commands, which are also named
`*Command` — do not confuse them with bus commands.

## Migrations

Schema changes go through Doctrine migrations in `migrations/`. Generate with
`php bin/console make:migration` inside the PHP container and review the SQL before keeping it.

The whole history must replay on an empty database: `make test-db` builds the test database
from it. Executed migrations never run again in production, so an old migration may be edited
only to make it replayable, with the same end schema; if it depends on state created outside
migrations, branch on `$schema->hasTable()` (see `Version20230814140723`).

## Broker sync

Accounts linked to a broker (`BrokerAccountLink`, one per account, encrypted token) get their
records from the broker instead of manual input. Code: `Investments/*/BrokerSync`.

* `broker:sync` (scheduled, and the "Sync now" button) imports operations into the
  `broker_operations` journal, then replays the whole journal (`Ledger\LedgerReplayer`: FIFO lots,
  shorts, trade fee allocation, share splits from `share_splits`) and writes the result into deals,
  dividends, coupons and investments matched by `external_id` (`LedgerProjector`).
  Cash balances come from the broker positions; `PositionsReconciler` reports differences.
* Records of a synced account carry `source = broker`; manual changes to such an account are
  refused (`SyncedAccountGuard`, HTTP 409). Unlinking keeps the records and starts a manual journal
  from them.
* A new broker: implement `Domain/BrokerSync/Client/BrokerClientInterface`, map its operation types
  to `BrokerOperationType`, add the provider to `BrokerProvider` and `BrokerClientFactory`.
* Tests use `tests/Investments/BrokerSync/FakeBrokerClient` (wired in `config/services.yaml` for
  `when@test`) and `Operations` to build broker operations.

## Manual journal

A manual account is rebuilt from its own journal, `manual_operations` (`Domain/Journal`,
`Application/Journal/ManualJournal`), the way a synced one is rebuilt from the broker's.

* Write paths record operations instead of changing deals or cash: a purchase or a short sale
  opens a lot (`op:<id>`), a sale closes a lot by key (one deal) or the oldest lots that are not
  blocked (by quantity, refused when more is asked than they hold), a block or an unblock marks a
  lot from its date, a cash adjustment records an edit of the cash in the account form.
* `ManualLedgerReplayer` replays the journal with `LotBook`; `ManualLedgerProjector` writes the
  lots into deals matched by `external_id` (the lot key), so deal ids survive rebuilds. A part
  sold off a lot keeps the key, the rest gets `#n` and the opening date of the purchase.
* Cash = what the trades moved (a bond with its accrued coupon, a future by its result when closed)
  less their commissions + cash adjustments + deposits + dividends (in the share's currency) +
  coupons. Changes of payouts and deposits go through `ManualJournal::changeRecords()`, which
  rebuilds the cash. Blocks of cash (`block_cash`) set aside the part of it that cannot be used
  (`account_cash.blocked`); the dashboard counts it as blocked assets.
* A trade entered by hand is charged by the account tariff (`Account::tradeCommission()`): a percent
  of a share or bond trade, a fixed sum per future. Deals recorded before have no known commission
  and are estimated from the current price, as before.
* An account without a journal (`accounts.journal_started_at` is null) starts one from its deals as
  they are on the first change; a cash adjustment keeps its cash. Run `accounts:rebuild` after a
  deploy that touches the journal: it starts the missing journals and rebuilds the others.
* The value of an account is computed on request (`AccountBalanceCalculator`): its cash by currency
  (`account_cash`) and its open deals at the current prices and rates.
* The "Operations" tab lists the journal (`GET /api/accounts/{id}/operations`, latest first). A
  sale, a block or a cash operation can be cancelled and a sale's price and date corrected
  (`ManualJournal::cancel()`, `correctSale()`); a purchase changes through its deal. Before a
  change the journal is replayed with it in memory, and the change is refused (HTTP 409) when the
  replay brings a warning the journal does not have now — a later sale or block that loses its lot
  (`#n` parts are numbered in the order of the sales) — or frees more cash than was blocked.
* The "Operations" page (`GET /api/operations`) shows the history of all accounts at once, filtered
  by account and `OperationCategory`: one SQL `UNION ALL` (`OperationHistoryRepository`) over the
  journals and the deposits, dividends and coupons of the manual accounts and the executed
  `broker_operations` of the synced ones (their projected records are left out, as duplicates).

## Instruments catalogue

Shares, bonds and futures live in one `instruments` table (Doctrine single table inheritance,
`kind` column): `Share`, `Bond` and `Future` extend `Investments\Domain\Instruments\Instrument`.
A ticker is unique on its exchange.

* Deals refer to an `Instrument` (`instrument_id`), dividends to a `Share`, coupons to a `Bond`;
  the records keep their ticker too, for securities the catalogue does not know.
* Doctrine cannot load an `Instrument` lazily: fetch-join `d.instrument` when loading lists of deals.
* A future values its points with the owner's multiplier, else with the exchange step price
  (`Future::getPointValue()`).

## Currency rates

`currency_rates` keeps one rate per currency and exchange day (MOEX indicative rates, the last one
of the day): `currency:get-rates` rewrites the current day every minute, `currency:get-rate-history`
fills in past days (daily for the last week; run it with `--from=<date>` to load more).

* What is held now is valued at the latest rate: `CurrencyService::getCurrencyRate()`.
* What happened in the past is valued at the rate of its day: `CurrencyService::getCurrencyRateOn()`.
  A deal's result in roubles is counted as the tax does: proceeds at the rate of the closing day
  less the cost at the rate of the opening day, so it includes the change of the rate.
