<?php

namespace LaravelPropertyBag\Exceptions;

use Exception;

class InvalidSettingsValue extends Exception
{
    /**
     * Failed key name.
     */
    protected ?string $failedKey = null;

    /**
     * Setting value is not definied in key's allowed values array.
     */
    public static function settingNotAllowed(string $key): static
    {
        $exception = new static(
            "Given value is not a registered allowed value for {$key}."
        );

        return $exception->setFailedKey($key);
    }

    /**
     * Set failed key name.
     */
    public function setFailedKey(string $key): static
    {
        $this->failedKey = $key;

        return $this;
    }

    /**
     * Return failed key name.
     */
    public function getFailedKey(): ?string
    {
        return $this->failedKey;
    }
}
