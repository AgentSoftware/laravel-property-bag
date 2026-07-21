<?php

namespace LaravelPropertyBag\Events;

use Illuminate\Database\Eloquent\Model;

class SettingReset
{
    /**
     * Construct.
     */
    public function __construct(
        public readonly Model $resource,
        public readonly string $key,
        public readonly mixed $oldValue,
        public readonly mixed $defaultValue,
    ) {}
}
