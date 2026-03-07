# AGENTS.md
Instructions for coding agents in this repository.

## Scope
- Backend: PHP 8.4, Symfony 7.2, Doctrine ORM.
- Frontend: Bootstrap, Vue 3, TypeScript, Vite.
- Package managers: Composer and npm.
- Main dirs: `src/`, `assets/`, `tests/`.

## Quick Start
- Install PHP deps: `composer install`.
- Install JS deps: `npm ci`.
- Initialize env: `cp .env .env.local`.

## Build, Lint, Test Commands

### Backend checks
- Psalm (CI style): `composer cs-check`.
- Psalm strict (ignore baseline): `composer cs-nobaseline`.
- Rebuild Psalm baseline: `composer cs-baseline`.
- Validate Symfony container wiring: `php bin/console lint:container`.

### PHPUnit
- Run all tests: `php bin/phpunit`.
- Run one file: `php bin/phpunit tests/Controller/ExpensesCategoryControllerTest.php`.
- Run one method: `php bin/phpunit --filter testIndex tests/Controller/ExpensesCategoryControllerTest.php`.
- Regex filter form: `php bin/phpunit --filter '/::testIndex$/' tests/Controller/ExpensesCategoryControllerTest.php`.

### Frontend checks
- Dev server: `npm run dev`.
- Production build: `npm run build`.
- ESLint: `npm run lint`.
- ESLint autofix: `npm run lint-fix`.

## CI Baseline
- Workflow `.github/workflows/tests.yml` runs:
  - `composer cs-check`
  - `npm ci`
  - `npm run build`
- For backend changes, run `composer cs-check`.
- For frontend changes, run ESLint and `npm run build`.

## Optional Docker/Makefile Workflow
- Start: `make up`.
- Stop: `make stop`.
- Restart: `make restart`.
- Build containers: `make build`.
- Rebuild containers: `make rebuild`.
- Enter PHP container: `make shell`.
- Install composer in container: `make composer-install`.
- Restore DB dump: `make restore-db`.
- Backup DB dump: `make backup-db`.

## Architecture Overview
- Bounded contexts under `src/`:
  - `Shared`
  - `Investments`
  - `Deposits`
  - `Expenses`
- Typical layers per context:
  - `Application`: controllers, DTOs, use cases, handlers.
  - `Domain`: entities, enums, interfaces, pure services.
  - `Infrastructure`: repository implementations, adapters.

## Dependency Direction Rules
- Prefer `Application -> Domain` dependencies.
- Keep infra details out of controllers/use cases.
- Define repository contracts in `Domain` as `*RepositoryInterface`.
- Implement repository contracts in `Infrastructure`.
- Inject interfaces into services/use cases/controllers.
- Avoid `EntityManager->getRepository()` in application layer.

## PHP Style Rules
- Always include `declare(strict_types=1);`.
- Namespace must mirror directory structure (PSR-4).
- Use 4 spaces indentation for PHP.
- Keep imports explicit and tidy.
- Prefer one class per file.
- Use typed properties/arguments/returns.
- Use PHPDoc for generic arrays/lists/shapes when needed.
- Use constructor injection and property promotion.
- Prefer immutable design when possible.
- Prefer `final` classes unless extension is intentional.
- Prefer `readonly` for immutable service/DTO classes.
- Keep methods focused and short where practical.
- Keep HTTP mapping logic in controllers, business logic elsewhere.

## Naming Conventions
- Class names: `PascalCase`.
- Methods/variables/properties: `camelCase`.
- Interface suffix: `Interface`.
- Data transfer objects: `*DTO`.
- Use cases: `*UseCase`.
- Controllers: `*Controller`.
- Commands/Queries: `*Command`, `*Query`.
- Handlers: `*Handler`.
- Compilers/mappers should use clear intent names (`*Compiler`, `*Mapper`).

## Error Handling Guidelines
- Throw domain/app-specific exceptions for business failures.
- In HTTP layer, map missing resources to 404.
- Avoid swallowing exceptions silently.
- Keep error messages actionable and specific.
- Prefer early return/guard clauses for invalid state.

## Validation and Request Mapping
- Use Symfony Validator attributes in request DTOs (`#[Assert\...]`).
- Use `#[MapRequestPayload]` for JSON body mapping where applicable.
- Keep request DTOs strict and typed.
- Do not pass raw request arrays deep into domain code.

## Symfony/API Conventions
- Use attribute routing (`#[Route(...)]`).
- Protect private endpoints with `#[IsGranted(...)]`.
- Use `#[CurrentUser] ?User $user` for auth-aware actions.
- Return API responses via `JsonResponse`.
- Keep controller responses stable when refactoring internals.

## Frontend Conventions
- TypeScript is strict (`tsconfig.json`), keep strong typing.
- Follow `.eslintrc.js` rules.
- Use alias imports `@/` for `assets/*`.
- Use 2 spaces for TS/Vue/JSON/YAML.
- Keep route pages in `assets/pages`.
- Keep reusable UI in `assets/components`.
- Keep shared state in Pinia stores under `assets/stores`.

## Suggested Agent Workflow
- Read nearby code before changing patterns.
- Match existing conventions in touched files.
- Make minimal, focused edits first.
- Run targeted tests before broad test runs.
- Re-run lint/build after significant changes.
- Document assumptions in PR/commit message.

## Commit Message Rules
- Commit messages are reviewed by the technical team.
- Use Conventional Commits format (`type: subject`).
- Prefer infinitive/imperative mood in English subjects (e.g. `refactor: extract homepage use case`).
- Keep commit subjects short and concise.
- Do not add long detailed descriptions in commit messages unless explicitly requested.

## Pre-Commit Checklist
- Changes are scoped to the task.
- No unrelated files are modified.
- Relevant tests pass.
- Psalm check passes for backend-impacting changes.
- Frontend build passes for UI-impacting changes.
- New repositories/services are injected via interfaces.
