---
name: check
description: Run the full local quality gate for the laravel-property-bag package (Pint, PHPStan, PHPUnit), optionally across all supported PHP versions via Docker.
---

# Check

Reproduces the two CI jobs defined in `.github/workflows/tests.yml` (`code-quality`
and `tests`) so you can catch failures before pushing.

## 1. Local quality gate (current PHP interpreter)

Run these in order, exactly as CI's `code-quality` job does:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

- `vendor/bin/pint --test` — style check, configured by `pint.json` (Laravel preset,
  excludes `src/Stubs`, enforces no unused imports, alphabetised imports, fully
  qualified strict types, `void` return types). Drop `--test` to have Pint fix
  violations in place.
- `vendor/bin/phpstan analyse` — static analysis at `level: max`, configured by
  `phpstan.neon.dist` (analyses `src`, excludes `src/Stubs`). CI runs this with
  `--no-progress`; that flag only suppresses the progress bar for log output and
  changes no behaviour, so it's optional locally.
- `vendor/bin/phpunit` — the test suite, using `phpunit.xml`.

### The platform.php pin only exercises the Laravel 12 line

`composer.json` pins `config.platform.php` to `8.2`. This tells Composer to *resolve*
dependencies as if the interpreter were PHP 8.2, regardless of the PHP version
actually running the command. Because Laravel 13 / `orchestra/testbench:^11`
require a newer PHP floor than 8.2, Composer will never select them under this
pin — a plain `composer install` or `composer update` always resolves to the
Laravel 12 / `orchestra/testbench:^10` line. To exercise Laravel 13 you must
unset the pin first (see below).

## 2. Reproduce a specific CI matrix leg natively

`tests.yml` matrixes `php: ['8.2','8.3','8.4','8.5']` × `laravel: [12, 13]`
(excluding `php 8.2` + `laravel 13`, since Laravel 13 needs a newer PHP floor),
mapping `laravel: 12 → testbench: '10'` and `laravel: 13 → testbench: '11'`. To
reproduce a leg — e.g. Laravel 13 / testbench 11 — run the same steps the
`tests` job runs:

```bash
composer config --unset platform.php
composer require --dev --no-update "laravel/framework:^13" "orchestra/testbench:^11"
composer update --prefer-dist --no-interaction
vendor/bin/phpunit
```

Swap `^13`/`^11` for `^12`/`^10` to reproduce the Laravel 12 leg instead. This
only changes the resolved dependency versions — it still runs under whatever
PHP interpreter is on your `PATH`, so it doesn't guarantee you're on a matching
PHP version unless you also switch interpreters (e.g. via `phpenv`/`asdf`) or
use Docker (below).

Restore the pin afterwards with `composer config platform.php 8.2` (or just
`git checkout -- composer.json composer.lock`) so your working tree doesn't
drift from the committed constraints.

## 3. Reproduce the full PHP matrix via Docker

`compose.yaml` is a dev-only harness (not referenced by `composer.json`, no
bearing on the published package) with one service per PHP version, each
running the same "unset platform pin → `composer update` → `phpunit`" dance
against real PHP binaries:

```bash
docker compose run --rm php82   # PHP 8.2-cli
docker compose run --rm php83   # PHP 8.3-cli
docker compose run --rm php84   # PHP 8.4-cli
docker compose run --rm php85   # PHP 8.5-cli
```

Each service unsets the platform pin and runs `composer update`, so Composer
resolves the newest Laravel/testbench line that's actually compatible with
that container's PHP version (e.g. `php82` will resolve to Laravel 12, since
Laravel 13 isn't installable under PHP 8.2). If you need a specific
Laravel/testbench version pinned inside a container, combine this with the
`composer require --dev --no-update ...` step from section 2.

No database or cache services are required — everything runs against sqlite.
