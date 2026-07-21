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
        // Factory intentionally supports subclassing via the `static` return type;
        // Exception's constructor signature is compatible with any subclass, so
        // `new static()` is safe here.
        // @phpstan-ignore new.static
        return new static(
            "Method {$method} for rule {$rule} not found. ".
            "Check rule spelling or create method {$method} in Rules.php."
        );
    }
}
