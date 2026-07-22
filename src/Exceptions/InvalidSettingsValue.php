<?php

namespace LaravelPropertyBag\Exceptions;

use Exception;

class InvalidSettingsValue extends Exception
{
    protected ?string $failedKey = null;

    public static function settingNotAllowed(string $key): static
    {
        // Factory intentionally supports subclassing via the `static` return type;
        // Exception's constructor signature is compatible with any subclass, so
        // `new static()` is safe here.
        // @phpstan-ignore new.static
        $exception = new static(
            "Given value is not a registered allowed value for {$key}."
        );

        return $exception->setFailedKey($key);
    }

    public function setFailedKey(string $key): static
    {
        $this->failedKey = $key;

        return $this;
    }

    public function getFailedKey(): ?string
    {
        return $this->failedKey;
    }
}
