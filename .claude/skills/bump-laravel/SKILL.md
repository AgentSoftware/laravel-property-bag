---
name: bump-laravel
description: Add support for a new Laravel major version to the laravel-property-bag package.
---

# Bump Laravel

Steps to add support for a new Laravel major version, using the existing
Laravel 12/13 support as the pattern to extend.

## 1. Widen the Composer constraints

In `composer.json`, add the new Laravel major to every `illuminate/*` require
and to `orchestra/testbench` in `require-dev`:

```json
"require": {
    "php": "^8.2",
    "illuminate/support": "^12.0||^13.0||^14.0",
    "illuminate/console": "^12.0||^13.0||^14.0",
    "illuminate/database": "^12.0||^13.0||^14.0",
    "illuminate/container": "^12.0||^13.0||^14.0"
},
"require-dev": {
    "orchestra/testbench": "^10.0||^11.0||^12.0"
}
```

Check the target Laravel version's own `composer.json` for its actual minimum
PHP requirement — this determines both the new PHP floor (see step 2) and
whether the existing `config.platform.php: 8.2` pin needs to move.

## 2. Add the matrix leg to `tests.yml`

`.github/workflows/tests.yml` currently matrixes:

```yaml
matrix:
  php: [ '8.2', '8.3', '8.4', '8.5' ]
  laravel: [ 12, 13 ]
  exclude:
    - php: '8.2'
      laravel: 13
include:
  - laravel: 12
    testbench: '10'
  - laravel: 13
    testbench: '11'
```

Follow the same pattern:

- Add the new PHP version(s) to `php:` if the new Laravel major needs a PHP
  version not already in the matrix.
- Add the new Laravel major to `laravel:`.
- Add an `include` entry mapping the new `laravel:` value to its corresponding
  `testbench:` major (check testbench's own compatibility table/`composer.json`
  for the correct mapping — it isn't 1:1 with the Laravel version number).
- Add an `exclude` entry for any PHP/Laravel combination that isn't installable
  (mirroring the existing `php: '8.2'` + `laravel: 13` exclusion), i.e. any PHP
  version in the matrix that's older than the new Laravel major's floor.

## 3. Add the matching `compose.yaml` service

`compose.yaml` has one service per PHP version, reusing the `&test` anchor:

```yaml
  php85:
    <<: *test
    image: php:8.5-cli
```

If the new Laravel major requires a PHP version not yet covered, add a new
`phpNN` service following this exact pattern (new key, same `<<: *test`
anchor, image `php:N.N-cli`). If it only requires PHP versions already
covered by existing services, no new service is needed.

## 4. Run the check skill

Run the `check` skill's full local gate, then reproduce the new leg both
natively (`composer config --unset platform.php`, `composer require --dev
--no-update "laravel/framework:^N" "orchestra/testbench:^M"`, `composer
update`, `vendor/bin/phpunit`) and via Docker (`docker compose run --rm
phpNN`) to confirm the new combination actually installs and passes before
committing the matrix change.

## 5. Update docs

Update `README.md` and any root `CLAUDE.md`/`AGENTS.md` that mention supported
Laravel/PHP versions so they don't go stale relative to `composer.json` and
`tests.yml`.
