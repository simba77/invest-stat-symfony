# Tests

PHPUnit 9, one suite over `tests/` (`phpunit.xml.dist`), namespace `App\Tests\`. Run inside the
PHP container; `make verify-php` recreates the test database and runs everything.

```bash
make test-db                                      # recreate the test database, load fixtures
docker exec $(docker ps -q -f name=invest-stat-symfony.php-fpm) php bin/phpunit
docker exec $(docker ps -q -f name=invest-stat-symfony.php-fpm) php bin/phpunit --testdox tests/Expenses
```

Reference implementation: `tests/Expenses/`.

## Layout

Tests mirror `src/`: `tests/<Context>/<Layer>/.../<Class>Test.php`.

* Controllers — functional tests extending `App\Tests\Support\ApiTestCase`: real kernel,
  router, security, validation and database.
* Other services that need the container or the database (Doctrine listeners, console
  commands) — `KernelTestCase` with `App\Tests\Support\InteractsWithDatabase`; console commands
  through `CommandTester`. Freeze time with Symfony's `ClockSensitiveTrait::mockTime()`:
  services read it from `Psr\Clock\ClockInterface`.
* Domain logic with real computation (calculations, grouping, tax) — unit tests on plain
  `TestCase`, no container and no database.
* Per-context test data helpers — a trait next to the tests (`tests/Expenses/CreatesExpenses.php`).

## Writing a controller test

* Name the test after the behaviour: `testEditLeavesOtherUsersCategoryUntouched`.
* Arrange / act / assert, separated by blank lines. Create the data the test needs inside the
  test; fixtures only hold the users (`$this->admin()`, `$this->otherUser()`).
* Call the API with `getJson()` / `postJson()` and assert the whole JSON with `assertSame()` —
  it is the contract with the SPA. Use ids of the entities the test created, never literals.
* After a write, check the database with `findFresh()` / `findFreshBy()`, not only the response.
* For every endpoint cover:
  * anonymous access → 403 (a data provider over all routes of the controller);
  * another user's resource → 404 and nothing changed in the database;
  * invalid payload → `assertViolatedFields([...])` (422 with `violations[].propertyPath`,
    what the frontend forms read); one data set per validation rule.

## Snapshots of read models

Pages with large computed responses (portfolio, account deals, analytics, dashboard, instrument
pages) are pinned by JSON snapshots: `App\Tests\Support\MatchesJsonSnapshots` compares the response
with `__snapshots__/<TestClass>/<name>.json` next to the test.

* Database ids depend on the order tests run in and are written as `<id>`; assert the ids that
  matter with `idsAt()`.
* A snapshot is the contract with the SPA: review its diff like code. After an intended change,
  rewrite it with `docker exec -e UPDATE_SNAPSHOTS=1 <php container> php bin/phpunit <test file>`.
* `tests/Investments/PortfolioScenario` builds a portfolio that covers every branch of the deal
  calculations; freeze time with `mockTime()` before building it.

## Test database

`make test-db` grants the app user access to `<db>_test`, drops and recreates it, runs all
migrations (the same schema as production) and loads `tests/Fixtures/` (registered only in the
`test` environment, `when@test` in `config/services.yaml`).

* `dama/doctrine-test-bundle` wraps every test in a transaction and rolls it back, so each
  test starts with just the fixtures and may write freely. Two limits: auto-increment values
  are not rolled back, and statements with an implicit commit (DDL, `TRUNCATE`) break the
  isolation.
* The bundle is pinned to `~8.2.0`: newer versions need PHPUnit 10+.
