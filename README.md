# Laravel Property Bag

Simple, secure settings for Laravel models, backed by a single `property_bag`
database table.

- Give any Eloquent model savable settings via one trait.
- Register allowed values and a default for each setting; invalid values throw
  an exception instead of silently persisting.
- Only non-default values are stored in the database, keeping the table small.
- Validate settings with fixed value lists or built-in/custom rules (`:int:`,
  `:range=1,10:`, etc.) instead of hardcoding every allowed value.

This is a maintained fork of the archived
[`zachleigh/laravel-property-bag`](https://github.com/zachleigh/laravel-property-bag)
(last upstream release `v1.4.1`, January 2020). See the [Attribution](#attribution)
section below.

### Contents
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Publishing config and migrations](#publishing-config-and-migrations)
  - [Usage](#usage)
  - [Methods](#methods)
  - [Validation Rules](#validation-rules)
  - [Configuration](#configuration)
  - [Artisan Commands](#artisan-commands)
  - [Running Tests](#running-tests)
  - [Contributing](#contributing)
  - [Attribution](#attribution)

### Requirements
  - PHP 8.2+
  - Laravel 12 or 13

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

### Usage

##### 1. Add the trait to your model

```php
use LaravelPropertyBag\Settings\HasSettings;

class User extends Model
{
    use HasSettings;

    ...
}
```

##### 2. Create a settings config class for the model

```
php artisan pbag:make User
```

This creates `app/Settings/UserSettings.php`, a class extending
`LaravelPropertyBag\Settings\ResourceConfig`. By default the package resolves
this class as `{App namespace}Settings\{Model}Settings` — e.g.
`App\Settings\UserSettings` for a `User` model in a standard Laravel app (see
[Configuration](#configuration) to change the namespace).

##### 3. Register allowed values and defaults

```php
protected $registeredSettings = [
    'example_setting' => [
        'allowed' => [true, false],
        'default' => false,
    ],
];
```

Each setting must have an array of allowed values (or a [validation
rule](#validation-rules) string) and a default value.

##### 4. Set values from the model

```php
$user->settings(['example_setting' => false]);
// or
$user->settings()->set(['example_setting' => false]);
// or
$user->setSettings(['example_setting' => false]);
```

Multiple values at once:

```php
$user->settings([
    'example_setting' => false,
    'another_setting' => 'grey',
]);
```

Setting a value that isn't in the `allowed` list (or doesn't satisfy its rule)
throws `LaravelPropertyBag\Exceptions\InvalidSettingsValue`. Use
`$e->getFailedKey()` to get the name of the setting that failed.

##### 5. Read values from the model

```php
$value = $user->settings('example_setting');
// or
$value = $user->settings()->get('example_setting');
```

If the value has not been explicitly set, the registered default is returned.
**Default values are never written to the database** — this keeps the table
small and means changing a default in code instantly applies to every
resource that hasn't overridden it.

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
`InvalidSettingsValue` if a value isn't allowed for its key.
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
default.
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
which model is used to read and write property bag rows.

### Artisan Commands

##### `php artisan pbag:make {resource}`
Creates `app/Settings/{Resource}Settings.php` from the package's
`ResourceConfig` stub, ready for you to fill in `$registeredSettings`.

##### `php artisan pbag:rules`
Creates `app/Settings/Resources/Rules.php` for defining
[user-defined validation rules](#user-defined-rules).

### Running Tests

Locally:

```
vendor/bin/phpunit
```

`composer.json` pins `config.platform.php` to `8.2`, so a plain
`composer install`/`update` always resolves the Laravel 12 / Testbench 10
line, regardless of the PHP interpreter actually running the tests — a local
`phpunit` run only exercises that line.

To exercise the full supported matrix (PHP 8.2–8.5 × Laravel 12/13, matching
`.github/workflows/tests.yml`), use the Docker Compose harness — one service
per PHP version, each unsetting the platform pin, running `composer update`,
and running the test suite against a real interpreter:

```
docker compose run --rm php82   # PHP 8.2-cli
docker compose run --rm php83   # PHP 8.3-cli
docker compose run --rm php84   # PHP 8.4-cli
docker compose run --rm php85   # PHP 8.5-cli
```

No database or cache services are required; everything runs against sqlite.

### Contributing

Contributions are welcome — fork, improve, and open a pull request. Before
submitting, run the quality gate:

```
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

Pint (Laravel preset) enforces code style, and PHPStan runs at strict
`level: max`. For bugs or ideas, open an
[issue](https://github.com/AgentSoftware/laravel-property-bag/issues).

### Attribution

This package was originally created by [Zach Leigh](https://github.com/zachleigh)
and is used here under the terms of its MIT license. See [LICENSE](LICENSE)
for full copyright details.
