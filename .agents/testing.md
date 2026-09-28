# Tests

PHPUnit, one suite over `tests/` (`phpunit.xml.dist`), namespace `App\Tests\`. Run inside the
PHP container; `make verify-php` recreates the test database and runs everything.

```bash
make test-db                                      # recreate the test database, load fixtures
docker exec $(docker ps -q -f name=invest-stat-symfony.php-fpm) php bin/phpunit
docker exec $(docker ps -q -f name=invest-stat-symfony.php-fpm) php bin/phpunit --filter testIndex tests/Controller/ExpensesCategoryControllerTest.php
```

## Kinds of tests

* Unit — `tests/<Context>/<Layer>/...`, mirroring `src/`. Plain `TestCase`, no container
  and no database. Prefer them for domain logic (calculations, grouping, tax).
* Functional — `tests/Controller/`, `WebTestCase` against the real kernel and the MariaDB
  test database `investstat_test`. Log in with `$client->loginUser()` as the fixture admin
  (`UserFixtures::ADMIN_EMAIL`) and compare JSON with the expected files in `tests/responses/`.

## Test database

`make test-db` grants the app user access to `<db>_test`, drops and recreates it, runs all
migrations (the same schema as production) and loads the fixtures from `tests/Fixtures/`.

* Fixtures are registered only in the `test` environment (`when@test` in `config/services.yaml`).
* One fixture class per context (`UserFixtures`, `ExpensesFixtures`, …); share entities via
  references and declare `DependentFixtureInterface`.
* The database is fresh, so auto-increment ids are predictable (the first category is `id: 1`)
  and expected JSON may rely on them.
* `dama/doctrine-test-bundle` wraps every test in a transaction and rolls it back, so each
  test starts with just the fixtures and may write freely. Two limits: auto-increment values
  are not rolled back (never assert ids of rows a test creates), and statements with an
  implicit commit (DDL, `TRUNCATE`) break the isolation.
* The bundle is pinned to `~8.2.0`: newer versions need PHPUnit 10+.
