<?php

namespace LaravelPropertyBag\Exceptions;

use Exception;

class ResourceNotFound extends Exception
{
    /**
     * Config file for resource can not be found.
     */
    public static function resourceConfigNotFound(string $namespace): static
    {
        // Factory intentionally supports subclassing via the `static` return type;
        // Exception's constructor signature is compatible with any subclass, so
        // `new static()` is safe here.
        // @phpstan-ignore new.static
        return new static("Class {$namespace} not found.");
    }
}
