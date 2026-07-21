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
        return new static("Class {$namespace} not found.");
    }
}
