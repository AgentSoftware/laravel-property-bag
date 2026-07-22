# Laravel Property Bag

Simple, secure settings for Laravel models, backed by a single `property_bag`
database table.

- Give any Eloquent model savable settings via one trait.
- Register allowed values and a default for each setting; invalid values throw
  an exception instead of silently persisting.
- Only non-default values are stored in the database, keeping the table small.
- Validate settings with fixed value lists or built-in/custom rules (`:int:`,
  `:range=1,10:`, etc.) instead of hardcoding every allowed value.
- Listen for `SettingUpdated`/`SettingReset` domain events to react to changes.

This is a maintained fork of the archived
[`zachleigh/laravel-property-bag`](https://github.com/zachleigh/laravel-property-bag)
(last upstream release `v1.4.1`, January 2020). See the [Attribution](#attribution)
section below.

### Contents
  - [Requirements](#requirements)
  - [Upgrading to v2.0](#upgrading-to-v20)
  - [Installation](#installation)
  - [Publishing config and migrations](#publishing-config-and-migrations)
  - [Getting Started](#getting-started)
  - [Methods](#methods)
  - [Validation Rules](#validation-rules)
  - [Events](#events)
  - [Error Handling & Logging](#error-handling--logging)
  - [Configuration](#configuration)
  - [Artisan Commands](#artisan-commands)
  - [Running Tests & Quality Checks](#running-tests--quality-checks)
  - [Contributing](#contributing)
  - [Attribution](#attribution)

### Requirements
  - PHP 8.2+
  - Laravel 12 or 13

### Upgrading to v2.0

> **Breaking change.** If you're coming from `v1.x` (including upstream
> `zachleigh/laravel-property-bag`), read this before upgrading.

- **Every model using the `HasSettings` trait must now also implement
  `LaravelPropertyBag\Contracts\HasSettings`.** This isn't just a style
  recommendation — it's enforced at runtime. `Settings::__construct()`
  type-hints its resource parameter as the native PHP intersection type
  `Model&HasSettings`:

  ```php
  public function __construct(ResourceConfig $settingsConfig, Model&HasSettings $resource)
  ```

  A model that `use`s the trait but doesn't `implements` the interface still
  satisfies `Model` but not `HasSettings`, so PHP throws a `TypeError` the
  first time settings are accessed on it (the trait constructs `Settings`
  lazily — see [Getting Started](#getting-started) for the required
  trait+interface pairing).
- **Raised floors:** PHP `^8.2` and Laravel `^12.0||^13.0`. Older PHP/Laravel
  versions are no longer supported.
- **New required dependencies:** `illuminate/support`, `illuminate/console`,
  `illuminate/database`, and `illuminate/container` (`^12.0||^13.0`) are now
  declared directly in `composer.json` rather than being implicitly supplied
  by a host application.

### Installation

`agentsoftware/laravel-property-bag` is distributed **privately** — it is not
published on Packagist. Point Composer at the GitHub repository directly via a
`vcs` repository entry in your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/AgentSoftware/laravel-property-bag"
        }
    ]
}
```

Then require the package as normal:

```
composer require agentsoftware/laravel-property-bag
```

The package's service provider (`LaravelPropertyBag\ServiceProvider`) is
registered automatically via Laravel's package auto-discovery
(`composer.json`'s `extra.laravel.providers` entry) — there is nothing to add
to your application's provider list.

### Publishing config and migrations

The package ships a config file and a migration for the `property_bag` table.
Publish each with its tag:

```
php artisan vendor:publish --tag=config
php artisan vendor:publish --tag=migrations
```

Then run the migration:

```
php artisan migrate
```

### Getting Started

##### 1. Define a settings config class

Scaffold one with the [`pbag:make`](#artisan-commands) command:

```
php artisan pbag:make User
```

This creates `app/Settings/UserSettings.php`, a class extending
`LaravelPropertyBag\Settings\ResourceConfig`. By default the package resolves
this class as `{App namespace}Settings\{Model}Settings` — e.g.
`App\Settings\UserSettings` for a `User` model in a standard Laravel app (see
[Configuration](#configuration) to change the namespace).

Register each setting's allowed values and default in `$registeredSettings`:

```php
<?php

namespace App\Settings;

use LaravelPropertyBag\Settings\ResourceConfig;

class UserSettings extends ResourceConfig
{
    protected $registeredSettings = [
        'newsletter' => [
            'allowed' => [true, false],
            'default' => true,
        ],

        'theme' => [
            'allowed' => ['light', 'dark', 'system'],
            'default' => 'system',
        ],

        'items_per_page' => [
            'allowed' => ':range=10,100:',
            'default' => 25,
        ],
    ];
}
```

Each setting needs an array of allowed values (or a [validation
rule](#validation-rules) string) and a default value.

If the allowed values or default for a setting can't be hardcoded (e.g. they
depend on config or another table), override `registeredSettings()` instead of
declaring the `$registeredSettings` property:

```php
<?php

namespace App\Settings;

use LaravelPropertyBag\Settings\ResourceConfig;

class UserSettings extends ResourceConfig
{
    public function registeredSettings()
    {
        return collect([
            'locale' => [
                'allowed' => array_keys(config('app.available_locales')),
                'default' => config('app.locale'),
            ],
        ]);
    }
}
```

##### 2. Wire the model

Add the `LaravelPropertyBag\Settings\HasSettings` trait to the model **and**
implement `LaravelPropertyBag\Contracts\HasSettings` — the trait and the
interface deliberately share the short name `HasSettings` (standard
Laravel trait+contract pairing), so alias one of the imports:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use LaravelPropertyBag\Contracts\HasSettings;
use LaravelPropertyBag\Settings\HasSettings as HasSettingsTrait;

class User extends Authenticatable implements HasSettings
{
    use HasSettingsTrait;

    // ...
}
```

See `tests/Classes/User.php` (and the other `tests/Classes/*` fixtures) for
the pattern in practice. As of v2.0 the `implements HasSettings` half is
**required** — see [Upgrading to v2.0](#upgrading-to-v20).

##### The `HasSettings` contract

`LaravelPropertyBag\Contracts\HasSettings` declares the trait's stable public
API — the methods the package (and your own code) can rely on any
`HasSettings`-using resource exposing:

| Method | Purpose |
| --- | --- |
| `propertyBag(): MorphMany` | The resource's `MorphMany` relation to its `PropertyBag` rows. |
| `settings(string\|array\|null $passed = null): mixed` | Get the `Settings` instance, read a single value, or set multiple values. |
| `setSettings(array $attributes): void` | Set one or more key/value pairs. |
| `setSettingsByRequest(): void` | Set all allowed settings from the current request. |
| `allSettings(): Collection` | All settings, keyed by name, with unset ones filled in from their defaults. |
| `defaultSetting(?string $key = null): mixed` | The registered default for a key, or all defaults. |
| `allowedSetting(?string $key = null): ?Collection` | The allowed values for a key, or all allowed values. |
| `withSetting(string $key, mixed $value = null): Collection` (static) | All resources with the given setting (and optionally value) set. |

Implementing it isn't optional — see [Upgrading to v2.0](#upgrading-to-v20)
for why a trait-only model throws a `TypeError` on first settings access.

##### 3. Set values from the model

```php
$user->settings(['newsletter' => false]);
// or
$user->settings()->set(['newsletter' => false]);
// or
$user->setSettings(['newsletter' => false]);
```

Multiple values at once:

```php
$user->settings([
    'newsletter' => false,
    'theme' => 'dark',
]);
```

Setting a value that isn't in the `allowed` list (or doesn't satisfy its rule)
throws `LaravelPropertyBag\Exceptions\InvalidSettingsValue`. Use
`$e->getFailedKey()` to get the name of the setting that failed.

##### 4. Read values from the model

```php
$value = $user->settings('newsletter');
// or
$value = $user->settings()->get('newsletter');
```

If the value has not been explicitly set, the registered default is returned.
**Default values are never written to the database** — this keeps the table
small and means changing a default in code instantly applies to every
resource that hasn't overridden it.

##### 5. Reset a value to its default

```php
$default = $user->settings()->reset('newsletter');
```

This deletes any stored row for the key and returns the (now active) default
value.

### Methods

All examples below use `$model->settings()`, which returns the
`LaravelPropertyBag\Settings\Settings` instance for the resource. Most methods
also have a shortcut directly on the model (via `HasSettings`), shown alongside.

##### `get(string $key): mixed`
Get the value for a given key, falling back to the registered default.
```php
$value = $model->settings()->get($key);
```

##### `set(array $attributes): void`
Set one or more key/value pairs. A value equal to its registered default is
not persisted (and any existing row for it is deleted). Throws
`InvalidSettingsValue` if a value isn't allowed for its key. Dispatches
[`SettingUpdated` or `SettingReset`](#events) per key changed.
```php
$model->settings()->set(['key1' => 'value1', 'key2' => 'value2']);
// or
$model->setSettings(['key1' => 'value1', 'key2' => 'value2']);
```

##### `getDefault(string $key): mixed`
Get the registered default value for a key.
```php
$default = $model->settings()->getDefault($key);
// or
$default = $model->defaultSetting($key);
```

##### `allDefaults(): Collection`
Get all registered default values, keyed by setting name.
```php
$defaults = $model->settings()->allDefaults();
// or
$defaults = $model->defaultSetting();
```

##### `getAllowed(string $key): ?Collection`
Get the allowed values for a key (`null` if the key isn't registered).
```php
$allowed = $model->settings()->getAllowed($key);
// or
$allowed = $model->allowedSetting($key);
```

##### `allAllowed(): Collection`
Get the allowed values for every registered setting, keyed by setting name.
```php
$allowed = $model->settings()->allAllowed();
// or
$allowed = $model->allowedSetting();
```

##### `isDefault(string $key, mixed $value): bool`
True if the given value is the default value for the key.
```php
$boolean = $model->settings()->isDefault($key, $value);
```

##### `isValid(string $key, mixed $value): bool`
True if the given value is allowed for the key.
```php
$boolean = $model->settings()->isValid($key, $value);
```

##### `all(): Collection`
All settings for the resource, keyed by setting name, with unset settings
filled in from their defaults.
```php
$allSettings = $model->settings()->all();
// or
$allSettings = $model->allSettings();
```

##### `keyIs(string $key, string $value): bool`
True if the setting for a key equals the given value.
```php
$boolean = $model->settings()->keyIs($key, $value);
```

##### `reset(string $key): mixed`
Reset a key to its default value (deleting any stored row) and return that
default. Dispatches [`SettingReset`](#events).
```php
$default = $model->settings()->reset($key);
```

##### `withSetting(string $key, mixed $value = null): Collection` (static)
Get all rows of the model that have a given setting key set, optionally
filtered to a specific value.
```php
$collection = $model::withSetting($key);
// or
$collection = $model::withSetting($key, $value);
```

##### Other `Settings` methods

A few lower-level, introspection-oriented methods are also public on
`$model->settings()`, without a model-level shortcut:

| Method | Returns |
| --- | --- |
| `isRegistered(string $key): bool` | Whether `$key` has an entry in `registeredSettings()`. |
| `isSaved(string $key): bool` | Whether `$key` has a non-default row persisted in `property_bag`. |
| `allSaved(): Collection` | Only the persisted (non-default) settings, keyed by name. |
| `getRegistered(): Collection` | The raw `['allowed' => ..., 'default' => ...]` config for every setting. |
| `getResourceConfig(): ResourceConfig` | The resource's `{Model}Settings` instance. |

### Validation Rules

Instead of hardcoding an array of allowed values, a setting's `allowed` value
can be a rule string. Rules are always strings wrapped in colons.

```php
'integer' => [
    'allowed' => ':int:',
    'default' => 7,
],
```

Some rules take parameters, passed after an `=` as a comma-separated list:

```php
'range' => [
    'allowed' => ':range=1,5:',
    'default' => 1,
],
```

`RuleValidator` parses the rule string, maps it to a `rule{Name}` method
(`:range:` → `ruleRange`), and dispatches to it — checking a user-defined
`Rules` class first, then falling back to the package's own
`LaravelPropertyBag\Settings\Rules\Rules`.

#### Built-in rules

| Rule | Accepts |
| --- | --- |
| `:any:` | Any value |
| `:alpha:` | Alphabetic values |
| `:alphanum:` | Alphanumeric values |
| `:bool:` | Boolean values |
| `:int:` | Integer values |
| `:num:` | Numeric values |
| `:range=low,high:` | Numeric values between (inclusive of) `low` and `high` |
| `:string:` | String values |

#### User-defined rules

Publish the rules stub to `app/Settings/Resources/Rules.php`:

```
php artisan pbag:rules
```

Define a rule by prefixing its name with `rule` and making it `static`:

```php
'setting_name' => [
    'allowed' => ':example:',
    'default' => 'default',
],
```

```php
public static function ruleExample(mixed $value): bool
{
    // return true/false
}
```

Rule methods that take parameters accept them as extra arguments, in the order
declared in the rule string:

```php
'setting_name' => [
    'allowed' => ':example=arg1,arg2:',
    'default' => 'default',
],
```

```php
public static function ruleExample(mixed $value, string $arg1, string $arg2): bool
{
    // return true/false
}
```

If neither the user-defined `Rules` class nor the built-in `Rules` class has a
matching `rule{Name}` method, `Settings::isValid()` (and therefore `set()`)
throws `LaravelPropertyBag\Exceptions\InvalidSettingsRule`.

### Events

Every call to `Settings::set()` dispatches one domain event per key changed —
`SettingUpdated` when a value is created or updated, `SettingReset` when a
value is set back to its default (which deletes the stored row instead of
writing it). Re-setting a key to the value it already holds — whether that's
the registered default or a previously-saved value — is a no-op and
dispatches nothing.

##### `LaravelPropertyBag\Events\SettingUpdated`

Dispatched when a non-default value is created or changed.

```php
public function __construct(
    public readonly Model $resource,
    public readonly string $key,
    public readonly mixed $oldValue,
    public readonly mixed $newValue,
    public readonly bool $wasCreated,
) {}
```

`$wasCreated` is `true` the first time a key gets a non-default value (no row
existed before), `false` when an existing row is updated to a new non-default
value.

##### `LaravelPropertyBag\Events\SettingReset`

Dispatched when a key is set (or [`reset()`](#methods)) back to its default
value.

```php
public function __construct(
    public readonly Model $resource,
    public readonly string $key,
    public readonly mixed $oldValue,
    public readonly mixed $defaultValue,
) {}
```

##### Example listener

```php
<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use LaravelPropertyBag\Events\SettingUpdated;

class LogSettingChange
{
    public function handle(SettingUpdated $event): void
    {
        Log::info("Setting '{$event->key}' changed", [
            'resource_type' => $event->resource->getMorphClass(),
            'resource_id' => $event->resource->getKey(),
            'old_value' => $event->oldValue,
            'new_value' => $event->newValue,
            'was_created' => $event->wasCreated,
        ]);
    }
}
```

Register it in a service provider's `boot()` method:

```php
use Illuminate\Support\Facades\Event;
use LaravelPropertyBag\Events\SettingUpdated;
use App\Listeners\LogSettingChange;

Event::listen(SettingUpdated::class, LogSettingChange::class);
```

In tests, use `Event::fake([SettingUpdated::class, SettingReset::class])` and
`Event::assertDispatched(...)` as usual — see `tests/Unit/EventTest.php` for
worked examples.

### Error Handling & Logging

The package's philosophy: failures are communicated through exceptions,
observable state changes through the two [events](#events) above — and it
does not log anything itself.

##### Exceptions

| Exception | Thrown when |
| --- | --- |
| `LaravelPropertyBag\Exceptions\InvalidSettingsValue` | A value passed to `set()`/`settings()` isn't in the setting's `allowed` list and doesn't satisfy its rule. Carries the failed key — see [`getFailedKey()`](#3-set-values-from-the-model). |
| `LaravelPropertyBag\Exceptions\InvalidSettingsRule` | A setting's `allowed` value references a rule (e.g. `:example:`) with no matching `rule{Name}` method on the user-defined or built-in `Rules` class. |
| `LaravelPropertyBag\Exceptions\ResourceNotFound` | The resolved `{Model}Settings` config class doesn't exist. |
| `\RuntimeException` | A property bag row couldn't be created, updated, or deleted (the underlying Eloquent `save()`/`delete()` call returned `false`), or a setting value couldn't be JSON-encoded for storage. |
| `\JsonException` | A stored `property_bag.value` isn't valid JSON when read back (`Settings` decodes with `JSON_THROW_ON_ERROR`) — i.e. the row was corrupted outside the package. |

##### Logging

`laravel-property-bag` never calls `Log::` (or any logger) itself. To observe
or log setting changes, listen for [`SettingUpdated` and
`SettingReset`](#events) (see the example listener above); to log or report
failures, catch the exceptions above — at the call site or in your
application's exception handler.

### Configuration

After publishing the config file (`--tag=config`), `config/property_bag.php`
exposes two keys:

##### `namespace`
Commented out by default. When unset, resource config and rules classes are
resolved under your application's own namespace, i.e.
`App\Settings\{Model}Settings` and `App\Settings\Resources\Rules`. Set this to
resolve them under a different namespace instead, e.g.:

```php
'namespace' => 'MyApp\\Settings',
```

which resolves to `MyApp\Settings\{Model}Settings` and
`MyApp\Settings\Resources\Rules`.

##### `model`
Defaults to `LaravelPropertyBag\Settings\PropertyBag::class`, the package's
bundled Eloquent model for the `property_bag` table. Set this to your own
model class (extending `LaravelPropertyBag\Settings\PropertyBag`) to override
which model is used to read and write property bag rows — for example, to
point the table at a different database connection:

```php
<?php

namespace App\Models;

use LaravelPropertyBag\Settings\PropertyBag;

class TenantPropertyBag extends PropertyBag
{
    protected $connection = 'tenant';
}
```

```php
// config/property_bag.php
'model' => \App\Models\TenantPropertyBag::class,
```

### Artisan Commands

##### `php artisan pbag:make {resource}`
Creates `app/Settings/{Resource}Settings.php` from the package's
`ResourceConfig` stub, ready for you to fill in `$registeredSettings`.

##### `php artisan pbag:rules`
Creates `app/Settings/Resources/Rules.php` for defining
[user-defined validation rules](#user-defined-rules).

### Running Tests & Quality Checks

Locally, via the `composer.json` scripts:

```
composer test              # vendor/bin/phpunit
composer test:coverage     # vendor/bin/phpunit --coverage-text
composer test:coverage-html # vendor/bin/phpunit --coverage-html build/coverage
composer lint               # vendor/bin/pint --test (check only)
composer lint:fix           # vendor/bin/pint (fix in place)
composer analyse            # vendor/bin/phpstan analyse --memory-limit=-1
composer check               # lint, then analyse, then test — the full local gate
```

`composer.json` pins `config.platform.php` to `8.2`, so a plain
`composer install`/`update` always resolves the Laravel 12 / Testbench 10
line, regardless of the PHP interpreter actually running the tests — a local
run only exercises that line.

To exercise the full supported matrix (PHP 8.2–8.5 × Laravel 12/13, matching
`.github/workflows/tests.yml`), use the Docker Compose harness (`compose.yaml`)
— one service per PHP version, each unsetting the platform pin, running
`composer update`, and running the test suite against a real interpreter:

```
docker compose run --rm php82   # PHP 8.2-cli
docker compose run --rm php83   # PHP 8.3-cli
docker compose run --rm php84   # PHP 8.4-cli
docker compose run --rm php85   # PHP 8.5-cli
```

There's also a dedicated `coverage` service (PHP 8.4-cli with PCOV installed),
for hosts without a local coverage driver:

```
docker compose run --rm coverage
```

No database or cache services are required; everything runs against sqlite.

### Contributing

Contributions are welcome — fork, improve, and open a pull request. Before
submitting, run the quality gate:

```
composer check
```

(equivalent to `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, then
`vendor/bin/phpunit` — see [Running Tests & Quality Checks](#running-tests--quality-checks)).
Pint (Laravel preset) enforces code style, and PHPStan runs at strict
`level: max`. For bugs or ideas, open an
[issue](https://github.com/AgentSoftware/laravel-property-bag/issues).

### Attribution

This package was originally created by [Zach Leigh](https://github.com/zachleigh)
and is used here under the terms of its MIT license. See [LICENSE](LICENSE)
for full copyright details.
