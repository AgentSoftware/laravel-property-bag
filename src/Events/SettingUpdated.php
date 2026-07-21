<?php

namespace LaravelPropertyBag\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class SettingUpdated
{
    use Dispatchable;

    /**
     * Construct.
     */
    public function __construct(
        public readonly Model $resource,
        public readonly string $key,
        public readonly mixed $oldValue,
        public readonly mixed $newValue,
        public readonly bool $wasCreated,
    ) {}
}
