<?php

namespace LaravelPropertyBag\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use LaravelPropertyBag\Settings\PropertyBag;

/**
 * Contract for resource models that use the LaravelPropertyBag\Settings\HasSettings
 * trait. Declares the trait's stable public API so the package can type-hint against
 * the resource without depending on the trait's implementation directly.
 */
interface HasSettings
{
    /**
     * A resource has many settings in a property bag.
     *
     * @return MorphMany<PropertyBag, Model>
     */
    public function propertyBag(): MorphMany;

    /**
     * If passed is string, get settings class for the resource or return value
     * for given key. If passed is array, set the key value pair.
     *
     * @param  array<string, mixed>|string|null  $passed
     */
    public function settings(string|array|null $passed = null): mixed;

    /**
     * Update or add multiple values to the settings table.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function setSettings(array $attributes): void;

    /**
     * Set all allowed settings by Request.
     */
    public function setSettingsByRequest(): void;

    /**
     * Get all settings.
     *
     * @return Collection<string, mixed>
     */
    public function allSettings(): Collection;

    /**
     * Get all default settings or default setting for single key if given.
     */
    public function defaultSetting(?string $key = null): mixed;

    /**
     * Get all allowed settings or allowed settings for single key if given.
     *
     * @return Collection<int, mixed>|Collection<string, array<int, mixed>|string>|null
     */
    public function allowedSetting(?string $key = null): ?Collection;

    /**
     * Get a collection with all resources with the given setting and/or value.
     *
     * @return Collection<int, static>
     */
    public static function withSetting(string $key, mixed $value = null): Collection;
}
