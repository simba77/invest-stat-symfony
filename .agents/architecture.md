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
  refused (`SyncedAccountGuard`, HTTP 409). Unlinking keeps the records and makes them editable.
* A new broker: implement `Domain/BrokerSync/Client/BrokerClientInterface`, map its operation types
  to `BrokerOperationType`, add the provider to `BrokerProvider` and `BrokerClientFactory`.
* Tests use `tests/Investments/BrokerSync/FakeBrokerClient` (wired in `config/services.yaml` for
  `when@test`) and `Operations` to build broker operations.
