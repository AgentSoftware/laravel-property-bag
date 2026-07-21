# Modernisation Plan

This document records the modernisation of `agentsoftware/laravel-property-bag`
carried out on `chore/modernise-laravel-13`: the state the fork was found in,
the decisions made before work started, and what each group of changes
delivered. It is a historical record of this effort, not living
documentation — see `README.md` and `CLAUDE.md` for how the package works
today.

## Version history

- Upstream (`zachleigh/laravel-property-bag`) is archived; its last release
  was `v1.4.1` (January 2020).
- This fork added a config-driven settings namespace on top of that in
  `v1.4.2` (commit `14bcc72`, 2021-12-29).
- The most recent release of this fork is **`v1.5.0`** (commit `090094e`,
  tagged 2026-07-08) — a dependency-only `composer.json` update, still on the
  2020-era stack described below.
- The work recorded in this document targets the **next** release, which will
  be **`v2.0.0`** — a breaking major, given the raised PHP/Laravel floor and
  the removal of support for older versions. No `CHANGELOG` is being
  introduced as part of this work (deliberately deferred).

## Audit: state before modernisation

As of `v1.5.0` (`090094e`), the package's tooling was frozen at a 2020-era
Laravel/PHP stack that no longer reflected how the code was actually written
or run:

- **`composer.json` required only `"php": ">=7.1"`**, with no `illuminate/*`
  dependency declared at all — despite `src/` using `Illuminate\Support`,
  `Illuminate\Database`, and `Illuminate\Console` classes throughout. The
  package worked in practice only because a host Laravel application always
  provided these classes at runtime.
- **The dev dependency stack was incoherent**: `require-dev` pulled in
  `laravel/laravel:^6.0`, `laravel/browser-kit-testing:~1.0`, and
  `phpunit/phpunit:~4.0` simultaneously — versions that do not form a
  installable, mutually-compatible set on any single PHP version.
- **CI never passed.** `.travis.yml` and `.scrutinizer.yml` were present but
  dead: given the dependency conflicts above, a clean install could not
  succeed, so neither pipeline could have been green. A StyleCI badge was
  linked from the README but had no corresponding config in the repo.
- The test suite predated Orchestra Testbench and used legacy PHPUnit
  conventions (`test`-prefixed method names rather than `#[Test]`
  attributes).
- No static analysis was configured anywhere in the repository.

## Locked decisions

Before implementation began, the following decisions were made and treated as
fixed constraints for all groups of work:

- **Target Laravel 12 and 13, PHP 8.2+.** Drop everything older.
- **Keep the `LaravelPropertyBag\` PHP namespace.** The package and vendor
  name change to `agentsoftware/laravel-property-bag`, but the namespace does
  not, to avoid a gratuitous breaking rename on top of the version-support
  break.
- **Distribute privately via a Composer VCS repository** — no Packagist
  submission.
- **Migrate the test suite to Orchestra Testbench**, replacing the legacy
  Laravel-application-based test harness.
- **Re-implement upstream PR #30** (config-driven `PropertyBag` model
  override), which had never been merged upstream, as part of this pass.
- **Add AgentSoftware attribution** alongside Zach Leigh's original copyright,
  rather than replacing it.
- **Base `main` on the `ddd-namespace` branch** (the branch containing the
  `v1.5.0` release state), rather than starting from `master`.
- **Tests-only CI, triggered on pull requests targeting `main`.** No
  deploy/publish automation, matching the private-VCS distribution model.
- **Static analysis at strict PHPStan `level: max`.**
- **A Docker-based harness** for exercising the full supported PHP/Laravel
  matrix locally, since the `composer.json` platform pin (see `CLAUDE.md`)
  otherwise limits a native `composer install` to a single line.
- **Package three Claude Code skills** (`check`, `release`, `bump-laravel`)
  capturing the recurring maintenance tasks this modernisation introduced.

## What each group delivered

- **Group A — composer spine.** Rewrote `composer.json`: declared
  `illuminate/support`, `illuminate/console`, `illuminate/database`,
  `illuminate/container` as real dependencies (`^12.0||^13.0`), raised the PHP
  floor to `^8.2`, and replaced the dev stack with
  `orchestra/testbench:^10.0||^11.0`, `laravel/pint`, and
  `larastan/larastan` + `phpstan-strict-rules`.
- **Group B1 — Testbench migration.** Migrated the test suite onto
  `Orchestra\Testbench\TestCase` (`tests/TestCase.php`), rebuilt the test
  fixtures/migrations to run against it, and got the suite green under the
  new stack.
- **Group B2 — config-driven `PropertyBag` model.** Re-implemented upstream
  PR #30: added the `model` key to `config/property_bag.php` and
  `PropertyBag::resolveModel()`, letting consumers override which Eloquent
  model backs the `property_bag` table.
- **Group B3 — native types and PHPUnit attributes.** Added native PHP
  parameter/return type declarations across `src/`, and converted the test
  suite from legacy `test`-prefixed methods to PHPUnit `#[Test]` attributes.
- **Group B4 — strict PHPStan.** Introduced `phpstan.neon.dist` at
  `level: max` with `larastan` and `phpstan-strict-rules`, and brought `src/`
  (excluding `src/Stubs`, which are templates, not compilable PHP) to a clean
  pass — including precise array/collection generic shapes on every
  parameter and return type that needed them.
- **Group C1 — CI, Pint, Docker.** Added `.github/workflows/tests.yml`
  (tests-only, PR-to-`main` trigger, matrixed across PHP 8.2–8.5 and Laravel
  12/13), `pint.json` (Laravel preset), `Dockerfile` and `compose.yaml` (a
  per-PHP-version dev harness for reproducing the full matrix locally), and
  removed the dead `.travis.yml`/`.scrutinizer.yml` configs. Also added three
  Claude Code skills (`check`, `release`, `bump-laravel`) documenting the
  resulting workflows.
- **Group C2 — documentation** (this change). Rewrote `README.md`,
  added this file and `CLAUDE.md`, and updated `LICENSE` attribution.
- **Group D — fork detachment.** Not yet performed as part of this work;
  planned as the step that severs this repository's relationship to the
  upstream `zachleigh/laravel-property-bag` fork.
