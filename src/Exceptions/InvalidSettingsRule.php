<?php

namespace LaravelPropertyBag\Exceptions;

use Exception;

class InvalidSettingsRule extends Exception
{
    /**
     * Setting rule method can not be found.
     */
    public static function ruleNotFound(string $rule, string $method): static
    {
        return new static(
            "Method {$method} for rule {$rule} not found. ".
            "Check rule spelling or create method {$method} in Rules.php."
        );
    }
}
