# CLAUDE.md

Guidance for AI agents (and human maintainers) working in this repository.

## Overview

`agentsoftware/laravel-property-bag` is a Laravel package giving Eloquent
models savable, validated settings backed by a single `property_bag` database
table. It is a maintained fork of the archived
[`zachleigh/laravel-property-bag`](https://github.com/zachleigh/laravel-property-bag)
(upstream last released `v1.4.1`, January 2020), currently being modernised
for Laravel 12/13 and PHP 8.2+ on `chore/modernise-laravel-13`. It is
distributed privately via a Composer VCS repository (not Packagist) — see
`README.md` for install instructions and `.claude/skills/release/SKILL.md` for
how releases are cut.

## Architecture

```
src/
├── ServiceProvider.php        Registers config, artisan commands, and
│                               publishable config/migration groups.
├── Helpers/
│   └── NameResolver.php        Resolves the {Model}Settings and Rules class
│                               names for a resource, honouring the
│                               `property_bag.namespace` config override.
├── Contracts/
│   └── HasSettings.php          Interface pairing the `Settings\HasSettings`
│                                 trait; declares its stable public API for
│                                 type-hinting the resource (see Conventions).
├── Settings/
│   ├── HasSettings.php          Trait consumers add to their models; exposes
│   │                            settings()/setSettings()/allSettings()/etc.
│   ├── ResourceConfig.php       Base class for per-model {Model}Settings
│   │                            classes (holds $registeredSettings).
│   ├── Settings.php             Core logic: validation, defaults, and
│   │                            persistence against the property bag.
│   ├── PropertyBag.php          The Eloquent model for the property_bag
│   │                            table; resolveModel() honours the
│   │                            `property_bag.model` config override.
│   └── Rules/
│       ├── RuleValidator.php    Parses ':rule=arg1,arg2:' strings and
│       │                        dispatches to Rules:: or a user-defined
│       │                        Rules class.
│       └── Rules.php            Built-in rules (:any:, :alpha:, :int:, etc.).
├── Commands/
│   ├── PbagCommand.php           Shared helpers (stub reading/replacement,
│   │                             directory creation) for the two commands.
│   ├── PublishSettingsConfig.php `pbag:make {resource}` — scaffolds
│   │                             app/Settings/{Resource}Settings.php.
│   └── PublishRulesFile.php      `pbag:rules` — scaffolds
│                                 app/Settings/Resources/Rules.php.
├── Stubs/                       Mustache-style ({{Namespace}}, {{ClassName}})
│                                 templates copied by the two commands above.
│                                 Not real PHP — excluded from Pint and
│                                 PHPStan (see below).
├── Exceptions/                  InvalidSettingsValue, InvalidSettingsRule,
│                                 ResourceNotFound.
└── Migrations/                  The property_bag table migration.
```

`config/property_bag.php` has two keys: `namespace` (where `{Model}Settings`/
`Rules` classes are resolved from; defaults to the app's own namespace) and
`model` (which Eloquent model backs the property bag; defaults to the
bundled `PropertyBag` class).

## Conventions

- **Namespace stays `LaravelPropertyBag\`.** This is a deliberate choice to
  avoid a breaking rename for existing consumers, even though the package
  name and vendor are now `agentsoftware/laravel-property-bag`. Do not
  "fix" this.
- **Strict PHPStan (`level: max`, `phpstan.neon.dist`), with mandatory array/
  collection generics.** Every array or `Collection` parameter/return must
  carry a precise shape, e.g. `Collection<string, array{allowed: array<int,
  mixed>|string, default: mixed}>`, not a bare `array`/`Collection`. Native
  PHP return/parameter types are used everywhere they can be; docblocks are
  kept only where they carry information PHP's type system can't express
  (generics, array shapes) or where they document *why* a type is what it is
  (see the `@phpstan-ignore` comments throughout `src/` — each one explains
  the specific reasoning, not just suppresses the error). `src/Stubs` is
  excluded from analysis (see below).
- **No `declare(strict_types=1)`.** No file in `src/` declares it and
  `pint.json` has no rule requiring it — don't add it as a "modernisation"
  side effect.
- **Pint style** (`pint.json`): Laravel preset, `src/Stubs` excluded,
  unused/unordered imports forbidden, fully-qualified strict types, `void`
  return types enforced where applicable.
- **`src/Stubs` are templates, not compilable PHP.** They contain
  `{{Namespace}}`/`{{ClassName}}` placeholders and are copied verbatim (with
  string replacement) by the publish commands into a consuming application.
  They are excluded from both Pint (`pint.json`) and PHPStan
  (`phpstan.neon.dist`) for that reason — don't try to "fix" them into valid
  PHP or remove the exclusion.
- **`Contracts\HasSettings` (interface) pairs with `Settings\HasSettings`
  (trait).** This is the standard Laravel interface+trait pairing: a
  consumer model `implements LaravelPropertyBag\Contracts\HasSettings` and
  `use`s `LaravelPropertyBag\Settings\HasSettings` to satisfy it, giving the
  package a real contract to type-hint the resource against (e.g. `Settings`
  types its `$resource` as `Model&HasSettings`) instead of a bare `Model`.
  Both share the short name `HasSettings` in different namespaces, so a
  consuming model must alias one import, e.g.:
  ```php
  use LaravelPropertyBag\Contracts\HasSettings;
  use LaravelPropertyBag\Settings\HasSettings as HasSettingsTrait;

  class User extends Model implements HasSettings
  {
      use HasSettingsTrait;
  }
  ```
  See `tests/Classes/User.php` (and the other `tests/Classes/*` fixtures) for
  the pattern in practice.
- **Prefer Laravel facades over global helper functions in package code**
  (e.g. `App::`/`Config::`/`Validator::` over `app()`/`config()`/
  `validator()`); helpers without a facade equivalent (`collect()`,
  `value()`, `data_get()`) are fine.
- **`config.platform.php: 8.2` pin in `composer.json`.** This forces Composer
  to resolve dependencies as if PHP 8.2 were the running interpreter,
  regardless of the actual interpreter — so a plain `composer install`/
  `update` always resolves the Laravel 12 / Testbench 10 line, never Laravel
  13. This is intentional: it keeps the lower-bound combination the default
  local dev environment, while CI (`.github/workflows/tests.yml`) explicitly
  unsets the pin to test both lines. Don't remove the pin without updating
  the reasoning here and in the `check`/`bump-laravel` skills.

## Test harness

Tests extend `LaravelPropertyBag\tests\TestCase` (`tests/TestCase.php`), which
extends `Orchestra\Testbench\TestCase`. It registers `ServiceProvider`, wires
an in-memory sqlite connection, and loads both the package's own migration
and the test app's fixture migrations (`tests/Migrations/`) via
`defineDatabaseMigrations()`. Fixture models/config classes live in
`tests/Classes/` (e.g. `User`/`UserConfig`, `Post`/`PostConfig`) and mirror
the pattern a real consumer would follow: a model using `HasSettings`, paired
with a `ResourceConfig` subclass. Tests use PHPUnit attributes (`#[Test]`
etc.), not the legacy `test`-prefixed method names or `@test` docblocks.

Run tests with `vendor/bin/phpunit` (uses `phpunit.xml`). See
`.claude/skills/check/SKILL.md` for the full local quality gate and how to
reproduce a specific CI matrix leg (native or via `docker compose run --rm
phpNN`, using `Dockerfile`/`compose.yaml`).

## Adding a new setting or validation rule

- **New setting on an existing resource:** add an entry to that resource's
  `{Model}Settings` class (`$registeredSettings`, or override
  `registeredSettings()` for dynamic allowed/default values — see
  `src/Settings/ResourceConfig.php`).
- **New built-in validation rule:** add a `public static function ruleX(mixed
  $value, ...$args): bool` method to `src/Settings/Rules/Rules.php`. The rule
  is then referenced as `:x:` (or `:x=arg1,arg2:`) in a setting's `allowed`
  value; `RuleValidator` dispatches to it by name.
- Add/extend PHPUnit tests under `tests/Unit/` for either change, following
  the existing fixture pattern in `tests/Classes/`.

## Project skills

Three skills live in `.claude/skills/` and should be preferred over ad-hoc
commands for their respective tasks:

- **`check`** — runs the full local quality gate (Pint, PHPStan, PHPUnit) and
  explains how to reproduce a specific CI matrix leg, natively or via Docker.
  Use before committing or opening a PR.
- **`bump-laravel`** — the steps to add support for a new Laravel major
  version (composer constraints, CI matrix, `compose.yaml` service, docs).
  Use instead of manually guessing which files need updating.
- **`release`** — cutting a manual, tagged release. This package has no
  Packagist publish step or release pipeline; releases are plain annotated
  git tags pushed to the private VCS remote, and the skill is a guide for a
  human decision, not an automation. Always confirm the version bump with the
  user before tagging or pushing.

## Quality gate

```
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

This mirrors the `code-quality` and `tests` jobs in
`.github/workflows/tests.yml`, which run on pull requests targeting `main`.
