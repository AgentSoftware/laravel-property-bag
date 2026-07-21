<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use LaravelPropertyBag\Exceptions\ResourceNotFound;
use LaravelPropertyBag\Helpers\NameResolver;

/**
 * Note: this trait is only consumed by application models outside this package
 * (e.g. tests/Classes/User.php), so PHPStan can't see a concrete class using it
 * from within src/ and reports the trait itself as unused/unanalysed. The return
 * types below are still kept precise for the benefit of consumers reading the
 * trait's docblocks and IDEs resolving them against the host model.
 *
 * @phpstan-ignore trait.unused
 */
trait HasSettings
{
    /**
     * Instance of Settings.
     */
    protected ?Settings $settings = null;

    /**
     * A resource has many settings in a property bag.
     *
     * @return MorphMany<PropertyBag, Model>
     */
    public function propertyBag(): MorphMany
    {
        return $this->morphMany(PropertyBag::resolveModel(), 'resource');
    }

    /**
     * If passed is string, get settings class for the resource or return value
     * for given key. If passed is array, set the key value pair.
     */
    public function settings(string|array|null $passed = null): mixed
    {
        if (is_array($passed)) {
            return $this->setSettings($passed);
        } elseif (! is_null($passed)) {
            $settings = $this->getSettingsInstance();

            return $settings->get($passed);
        }

        return $this->getSettingsInstance();
    }

    /**
     * Get settings off this or create new instance.
     */
    protected function getSettingsInstance(): Settings
    {
        if (isset($this->settings)) {
            return $this->settings;
        }

        $settingsConfig = $this->getSettingsConfig();

        return $this->settings = new Settings($settingsConfig, $this);
    }

    /**
     * Get the settings class name.
     *
     * @throws ResourceNotFound
     */
    protected function getSettingsConfig(): ResourceConfig
    {
        if (isset($this->settingsConfig)) {
            $fullNamespace = $this->settingsConfig;
        } else {
            $className = $this->getShortClassName();

            $fullNamespace = NameResolver::makeConfigFileName($className);
        }

        if (class_exists($fullNamespace)) {
            return new $fullNamespace($this);
        }

        throw ResourceNotFound::resourceConfigNotFound($fullNamespace);
    }

    /**
     * Get the short name of the model.
     */
    protected function getShortClassName(): string
    {
        $reflection = new \ReflectionClass($this);

        return $reflection->getShortName();
    }

    /**
     * Set settings.
     *
     * Note: void, not Settings, because Settings::set() itself returns void
     * (see its docblock) - matching the pre-existing behaviour rather than
     * the previous (inaccurate) `@return Settings`.
     */
    public function setSettings(array $attributes): void
    {
        $this->settings()->set($attributes);
    }

    /**
     * Set all allowed settings by Request.
     *
     * Note: void, for the same reason as setSettings() above.
     */
    public function setSettingsByRequest(): void
    {
        $allAllowedSettings = array_keys($this->allSettings()->toArray());

        $this->settings()->set(Request::only($allAllowedSettings));
    }

    /**
     * Get all settings.
     *
     * @return Collection<string, mixed>
     */
    public function allSettings(): Collection
    {
        return $this->settings()->all();
    }

    /**
     * Get all default settings or default setting for single key if given.
     */
    public function defaultSetting(?string $key = null): mixed
    {
        if (! is_null($key)) {
            return $this->settings()->getDefault($key);
        }

        return $this->settings()->allDefaults();
    }

    /**
     * Get all allowed settings or allowed settings for single ke if given.
     *
     * @return Collection<int, mixed>|Collection<string, array<int, mixed>|string>|null
     */
    public function allowedSetting(?string $key = null): ?Collection
    {
        if (! is_null($key)) {
            return $this->settings()->getAllowed($key);
        }

        return $this->settings()->allAllowed();
    }

    /**
     * Get a collection with all users with the given setting and/or value.
     *
     * @return Collection<int, static>
     */
    public static function withSetting(string $key, mixed $value = null): Collection
    {
        return static::all()->filter(function ($row) use ($key, $value) {
            $setting = $row->settings($key);

            if (! is_null($value)) {
                return ! is_null($setting) && $setting === $value;
            }

            return ! is_null($setting);
        });
    }
}
