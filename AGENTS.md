# AGENTS.md

Personal investment statistics tracker: a Symfony JSON API (`/api/*`) plus a Vue SPA.

Stack: PHP 8.4, Symfony 7.2, Doctrine ORM, MariaDB, Symfony Messenger; Vue 3, TypeScript, Pinia, Bootstrap, Vite.

## Topic Guides (read on demand)

Before touching the listed area, READ the matching guide first:

* Bounded contexts, layers, dependency rules, repositories, command/query buses → `.agents/architecture.md`
* Controllers, routes, request DTOs and validation, response DTOs and compilers, pagination → `.agents/api.md`
* Vue pages, components, composables, stores, API calls, types → `.agents/frontend.md`
* Writing or running tests → `.agents/testing.md`

## Core Rules

* Keep changes minimal and scoped to the task; do not touch unrelated contexts.
* Keep controller responses stable when refactoring internals — the SPA depends on them.
* Repository contracts live in `Domain` as `*RepositoryInterface`; inject interfaces, never `EntityManager->getRepository()` in `Application`.
* Always `declare(strict_types=1);`, typed signatures, constructor property promotion.
* Prefer `final` for new classes and `readonly` for immutable services and DTOs.
* PHPDoc only for what native types cannot express (generic arrays, shapes, templates).
* Throw specific exceptions for business failures; never swallow them silently.

## Verification

Run before reporting back and fix what it reports:

* Backend changes: `make verify-php` — recreates the test database, then Psalm, `lint:container`, PHPUnit (inside the PHP container).
* Frontend changes: `make verify-js` — ESLint and the Vite build (on the host).
* Both: `make verify`.

Rebuild the Psalm baseline (`composer cs-baseline`) only when asked; a growing baseline needs a reason.

## Docker

The dev stack runs in Docker (`make up`, `make shell`); the PHP container is
`invest-stat-symfony.php-fpm`. `DATABASE_URL` points at the `mariadb` host, so anything that
needs the database (console commands, migrations, functional tests) runs inside the container:
`docker exec $(docker ps -q -f name=invest-stat-symfony.php-fpm) <command>`.

## Commit Messages

* Conventional Commits: `type: subject`, e.g. `refactor: extract homepage use case`.
* Imperative English, short subject, no body unless asked.
