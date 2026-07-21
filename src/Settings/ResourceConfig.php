<?php

namespace LaravelPropertyBag\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ResourceConfig
{
    /**
     * Resource that has settings.
     */
    private Model $resource;

    /**
     * Registered settings for model.
     *
     * Note: left untyped natively (rather than `array`) because subclasses
     * generated from src/Stubs/ResourceConfig.php redeclare this property
     * without a type, and PHP requires matching property types across
     * inheritance.
     *
     * @var array<string, array{allowed: array<int, mixed>|string, default: mixed}>
     */
    protected $registeredSettings = [];

    /**
     * Construct.
     */
    public function __construct(Model $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Returns resource.
     */
    public function getResource(): Model
    {
        return $this->resource;
    }

    /**
     * Return a collection of registered settings.
     *
     * Note: no native return type here because tests/Classes/PostConfig.php
     * overrides this method without one; PHP requires overrides to declare a
     * compatible return type once the parent declares one.
     *
     * @return Collection<string, array{allowed: array<int, mixed>|string, default: mixed}>
     */
    public function registeredSettings()
    {
        return collect($this->registeredSettings);
    }
}
