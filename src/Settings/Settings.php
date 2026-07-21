<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
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
    protected Model $resource;

    /**
     * Registered keys, values, and defaults.
     * 'key' => ['allowed' => $value, 'default' => $value].
     */
    protected Collection $registered;

    /**
     * Settings saved in database. Does not include defaults.
     */
    protected Collection $settings;

    /**
     * Validator for allowed rules.
     */
    protected RuleValidator $ruleValidator;

    /**
     * Construct.
     */
    public function __construct(ResourceConfig $settingsConfig, Model $resource)
    {
        $this->settingsConfig = $settingsConfig;
        $this->resource = $resource;

        $this->ruleValidator = new RuleValidator;
        $this->registered = $settingsConfig->registeredSettings();

        $this->sync();
    }

    /**
     * Get the property bag relationshp off the resource.
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

        if (! is_array($allowed) &&
            $rule = $this->ruleValidator->isRule($allowed)) {
            return $this->ruleValidator->validate($rule, $value);
        }

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
        if ($this->isRegistered($key)) {
            return $this->getRegistered()[$key]['default'];
        }

        return null;
    }

    /**
     * Return all settings used by resource, including defaults.
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
     */
    public function allDefaults(): Collection
    {
        return $this->getRegistered()->map(function ($value) {
            return $value['default'];
        });
    }

    /**
     * Get the allowed settings for key.
     */
    public function getAllowed(string $key): ?Collection
    {
        if ($this->isRegistered($key)) {
            return collect($this->getRegistered()[$key]['allowed']);
        }

        return null;
    }

    /**
     * Get all allowed values for settings.
     */
    public function allAllowed(): Collection
    {
        return $this->getRegistered()->map(function ($value) {
            return $value['allowed'];
        });
    }

    /**
     * Get all saved settings. Default values are not included in this output.
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
     */
    public function set(array $attributes): void
    {
        collect($attributes)->each(function ($value, $key) {
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
    public function keyIs(string $key, string $value): bool
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
            return $this->deleteRecord($key);
        } elseif ($this->isDefault($key, $value)) {
            return null;
        } elseif ($this->isSaved($key)) {
            return $this->updateRecord($key, $value);
        }

        return $this->createRecord($key, $value);
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
     */
    protected function createRecord(string $key, mixed $value): PropertyBag
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
        $record = $this->getByKey($key);

        $record->value = $this->valueToJson($value);

        $record->save();

        return $record;
    }

    /**
     * Json encode value.
     */
    protected function valueToJson(mixed $value): string
    {
        return json_encode([$value]);
    }

    /**
     * Delete a PropertyBag record.
     */
    protected function deleteRecord(string $key): void
    {
        $this->getByKey($key)->delete();
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
     * Load settings from the resource relationship on to this.
     */
    protected function sync(): void
    {
        $this->settings = $this->getAllSettingsFlat();
    }

    /**
     * Get all settings as a flat collection.
     */
    protected function getAllSettingsFlat(): Collection
    {
        return $this->getAllSettings()->flatMap(function (Model $model) {
            return [$model->key => json_decode($model->value)[0]];
        });
    }

    /**
     * Retrieve all settings from database.
     */
    protected function getAllSettings(): Collection
    {
        if ($this->resource->relationLoaded('propertyBag')) {
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
