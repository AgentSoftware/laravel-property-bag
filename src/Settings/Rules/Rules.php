<?php

namespace LaravelPropertyBag\Settings\Rules;

class Rules
{
    /**
     * Return true if value is alpha characters.
     */
    public static function ruleAlpha(mixed $value): bool
    {
        // value is a registered setting's raw input, expected to be scalar; casting
        // mixed to string preserves the existing validation behaviour for numeric/
        // bool inputs.
        // @phpstan-ignore cast.string
        return ctype_alpha((string) $value);
    }

    /**
     * Return true for everything.
     */
    public static function ruleAny(): bool
    {
        return true;
    }

    /**
     * Return true if value is alpha characters.
     */
    public static function ruleAlphanum(mixed $value): bool
    {
        // value is a registered setting's raw input, expected to be scalar; casting
        // mixed to string preserves the existing validation behaviour for numeric/
        // bool inputs.
        // @phpstan-ignore cast.string
        return ctype_alnum((string) $value);
    }

    /**
     * Return true if value is alpha characters.
     */
    public static function ruleBool(mixed $value): bool
    {
        return is_bool($value);
    }

    /**
     * Return true if value is integer.
     */
    public static function ruleInt(mixed $value): bool
    {
        return is_int($value);
    }

    /**
     * Return true if value is numeric.
     */
    public static function ruleNum(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Return true if value is numeric.
     */
    public static function ruleRange(mixed $value, int $low, int $high): bool
    {
        return ($low <= $value) && ($value <= $high);
    }

    /**
     * Return true if value is a string.
     */
    public static function ruleString(mixed $value): bool
    {
        return is_string($value);
    }
}
