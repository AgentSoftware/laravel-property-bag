<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use LaravelPropertyBag\Contracts\HasSettings;
use LaravelPropertyBag\Events\SettingReset;
use LaravelPropertyBag\Events\SettingUpdated;
use LaravelPropertyBag\Exceptions\InvalidSettingsValue;
use LaravelPropertyBag\Settings\Rules\RuleValidator;

class Settings
{
    /**
     * Settings for resource.
     */
    protected ResourceConfig $settingsConfig;

    /**
     * Resource that has settings.
     */
    protected Model&HasSettings $resource;

    /**
     * Registered keys, values, and defaults.
     * 'key' => ['allowed' => $value, 'default' => $value].
     *
     * @var Collection<string, array{allowed: array<int, mixed>|string, default: mixed}>
     */
    protected Collection $registered;

    /**
     * Settings saved in database. Does not include defaults.
     *
     * @var Collection<string, mixed>
     */
    protected Collection $settings;

    /**
     * Validator for allowed rules.
     */
    protected RuleValidator $ruleValidator;

    /**
     * Construct.
     */
    public function __construct(ResourceConfig $settingsConfig, Model&HasSettings $resource)
    {
        $this->settingsConfig = $settingsConfig;
        $this->resource = $resource;

        $this->ruleValidator = new RuleValidator;
        $this->registered = $settingsConfig->registeredSettings();

        $this->sync();
    }

    /**
     * Get the property bag relationshp off the resource.
     *
     * @return MorphMany<PropertyBag, Model>
     */
    protected function propertyBag(): MorphMany
    {
        return $this->resource->propertyBag();
    }

    /**
     * Get resource config.
     */
    public function getResourceConfig(): ResourceConfig
    {
        return $this->settingsConfig;
    }

    /**
     * Get registered settings.
     *
     * @return Collection<string, array{allowed: array<int, mixed>|string, default: mixed}>
     */
    public function getRegistered(): Collection
    {
        return $this->registered;
    }

    /**
     * Return true if key exists in registered settings collection.
     */
    public function isRegistered(string $key): bool
    {
        return $this->getRegistered()->has($key);
    }

    /**
     * Return true if key and value are registered values.
     */
    public function isValid(string $key, mixed $value): bool
    {
        $settings = collect(
            $this->getRegistered()->get($key, ['allowed' => []])
        );

        $allowed = $settings->get('allowed');

        if (! is_array($allowed)) {
            // $allowed is expected to be a scalar rule descriptor (e.g. ':alpha:')
            // when it isn't an array; casting mixed to string preserves the
            // existing behaviour for that case.
            // @phpstan-ignore cast.string
            $rule = $this->ruleValidator->isRule((string) $allowed);

            if (is_string($rule)) {
                return $this->ruleValidator->validate($rule, $value);
            }
        }

        // $allowed is an array<int, mixed> for valid registeredSettings config. A
        // malformed 'allowed' value (neither an array nor a rule string) already
        // fails at runtime here, matching the prior behaviour.
        // @phpstan-ignore argument.type
        return in_array($value, $allowed, true);
    }

    /**
     * Return true if value is default value for key.
     */
    public function isDefault(string $key, mixed $value): bool
    {
        return $this->getDefault($key) === $value;
    }

    /**
     * Get the default value from registered.
     */
    public function getDefault(string $key): mixed
    {
        $registered = $this->getRegistered()->get($key);

        return $registered !== null ? $registered['default'] : null;
    }

    /**
     * Return all settings used by resource, including defaults.
     *
     * @return Collection<string, mixed>
     */
    public function all(): Collection
    {
        $saved = $this->allSaved();

        return $this->allDefaults()->map(function ($value, $key) use ($saved) {
            if ($saved->has($key)) {
                return $saved->get($key);
            }

            return $value;
        });
    }

    /**
     * Get all defaults for settings.
     *
     * @return Collection<string, mixed>
     */
    public function allDefaults(): Collection
    {
        return $this->getRegistered()->map(function ($value) {
            return $value['default'];
        });
    }

    /**
     * Get the allowed settings for key.
     *
     * @return Collection<int, mixed>|null
     */
    public function getAllowed(string $key): ?Collection
    {
        $registered = $this->getRegistered()->get($key);

        if ($registered === null) {
            return null;
        }

        $allowed = $registered['allowed'];

        // collect() on a string already wraps it as a single-element array
        // (Collection::getArrayableItems() falls back to an (array) cast), so this
        // is behaviourally identical to the previous `collect($allowed)` for both
        // array and rule-string 'allowed' values.
        return collect(is_array($allowed) ? $allowed : [$allowed]);
    }

    /**
     * Get all allowed values for settings.
     *
     * @return Collection<string, array<int, mixed>|string>
     */
    public function allAllowed(): Collection
    {
        return $this->getRegistered()->map(function ($value) {
            return $value['allowed'];
        });
    }

    /**
     * Get all saved settings. Default values are not included in this output.
     *
     * @return Collection<string, mixed>
     */
    public function allSaved(): Collection
    {
        return collect($this->settings);
    }

    /**
     * Update or add multiple values to the settings table.
     *
     * Note: returns void, not static/self, because it returns the result of
     * sync() (also void) - this reflects the pre-existing behaviour rather
     * than the previous docblock's (inaccurate) `@return static`.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(array $attributes): void
    {
        collect($attributes)->each(function ($value, $key): void {
            $this->setKeyValue($key, $value);
        });

        // If we were working with eagerly-loaded relation,
        // we need to reload its data to be sure that we
        // are working only with the actual settings.

        if ($this->resource->relationLoaded('propertyBag')) {
            $this->resource->load('propertyBag');
        }

        $this->sync();
    }

    /**
     * Return true if key is set to value.
     */
    public function keyIs(string $key, mixed $value): bool
    {
        return $this->get($key) === $value;
    }

    /**
     * Reset key to default value. Return default value.
     */
    public function reset(string $key): mixed
    {
        $default = $this->getDefault($key);

        $this->set([$key => $default]);

        return $default;
    }

    /**
     * Set a value to a key in local and database settings.
     */
    protected function setKeyValue(string $key, mixed $value): mixed
    {
        $this->validateKeyValue($key, $value);

        if ($this->isDefault($key, $value) && $this->isSaved($key)) {
            $oldValue = $this->allSaved()->get($key);

            $this->deleteRecord($key);

            Event::dispatch(new SettingReset($this->resource, $key, $oldValue, $this->getDefault($key)));

            return null;
        } elseif ($this->isDefault($key, $value)) {
            return null;
        } elseif ($this->isSaved($key)) {
            $oldValue = $this->allSaved()->get($key);

            $record = $this->updateRecord($key, $value);

            if ($oldValue !== $value) {
                Event::dispatch(new SettingUpdated($this->resource, $key, $oldValue, $value, wasCreated: false));
            }

            return $record;
        }

        $record = $this->createRecord($key, $value);

        Event::dispatch(new SettingUpdated($this->resource, $key, $this->getDefault($key), $value, wasCreated: true));

        return $record;
    }

    /**
     * Throw exception if key/value invalid.
     *
     * @throws InvalidSettingsValue
     */
    protected function validateKeyValue(string $key, mixed $value): void
    {
        if (! $this->isValid($key, $value)) {
            throw InvalidSettingsValue::settingNotAllowed($key);
        }
    }

    /**
     * Return true if key is already saved in database.
     */
    public function isSaved(string $key): bool
    {
        return $this->allSaved()->has($key);
    }

    /**
     * Create a new PropertyBag record.
     *
     * Note: save() on the relation can return false on failure, matching
     * Illuminate\Database\Eloquent\Relations\HasOneOrMany::save()'s own contract.
     */
    protected function createRecord(string $key, mixed $value): PropertyBag|false
    {
        $propertyBagModel = PropertyBag::resolveModel();

        return $this->propertyBag()->save(
            new $propertyBagModel([
                'key' => $key,
                'value' => $this->valueToJson($value),
            ])
        );
    }

    /**
     * Update a PropertyBag record.
     */
    protected function updateRecord(string $key, mixed $value): PropertyBag
    {
        $record = $this->getRecordOrFail($key);

        $record->value = $this->valueToJson($value);

        $record->save();

        return $record;
    }

    /**
     * Json encode value.
     */
    protected function valueToJson(mixed $value): string
    {
        $json = json_encode([$value]);

        if ($json === false) {
            throw new \RuntimeException('Unable to encode setting value as JSON.');
        }

        return $json;
    }

    /**
     * Delete a PropertyBag record.
     */
    protected function deleteRecord(string $key): void
    {
        $this->getRecordOrFail($key)->delete();
    }

    /**
     * Get a property bag record by key.
     */
    protected function getByKey(string $key): ?PropertyBag
    {
        return $this->propertyBag()
            ->where('resource_id', $this->resource->getKey())
            ->where('key', $key)
            ->first();
    }

    /**
     * Get a property bag record by key, or fail.
     *
     * Only called from setKeyValue() after isSaved($key) has confirmed a matching
     * record exists, so this should never actually throw.
     */
    protected function getRecordOrFail(string $key): PropertyBag
    {
        $record = $this->getByKey($key);

        if ($record === null) {
            throw new \RuntimeException("No settings record found for key {$key}.");
        }

        return $record;
    }

    /**
     * Load settings from the resource relationship on to this.
     */
    protected function sync(): void
    {
        $this->settings = $this->getAllSettingsFlat();
    }

    /**
     * Get all settings as a flat collection.
     *
     * @return Collection<string, mixed>
     */
    protected function getAllSettingsFlat(): Collection
    {
        return $this->getAllSettings()->flatMap(function (PropertyBag $model) {
            // json_decode() returns mixed by definition, so its offset access can't
            // be narrowed further without changing the (unrelated) decoding
            // behaviour.
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            return [$model->key => json_decode($model->value)[0]];
        });
    }

    /**
     * Retrieve all settings from database.
     *
     * @return Collection<int, PropertyBag>
     */
    protected function getAllSettings(): Collection
    {
        if ($this->resource->relationLoaded('propertyBag')) {
            // The loaded relation is guaranteed to be a Collection<int, PropertyBag>
            // at runtime since propertyBag() always resolves to a PropertyBag-backed
            // relation; Model's generic magic property access can't express this
            // loaded-relation type statically (see propertyBag() above).
            // @phpstan-ignore property.notFound, return.type
            return $this->resource->propertyBag;
        }

        return $this->propertyBag()
            ->where('resource_id', $this->resource->getKey())
            ->get();
    }

    /**
     * Get value from settings by key. Get registered default if not set.
     */
    public function get(string $key): mixed
    {
        return $this->allSaved()->get($key, function () use ($key) {
            return $this->getDefault($key);
        });
    }
}
