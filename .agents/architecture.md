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
