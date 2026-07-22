<?php

namespace LaravelPropertyBag\Settings\Rules;

class Rules
{
    public static function ruleAlpha(mixed $value): bool
    {
        // value is a registered setting's raw input, expected to be scalar; casting
        // mixed to string makes ctype_* operate on the string form of the value
        // (e.g. an int or bool argument), which is the intended, sane behaviour.
        // @phpstan-ignore cast.string
        return ctype_alpha((string) $value);
    }

    public static function ruleAny(): bool
    {
        return true;
    }

    public static function ruleAlphanum(mixed $value): bool
    {
        // value is a registered setting's raw input, expected to be scalar; casting
        // mixed to string makes ctype_* operate on the string form of the value
        // (e.g. an int or bool argument), which is the intended, sane behaviour.
        // @phpstan-ignore cast.string
        return ctype_alnum((string) $value);
    }

    public static function ruleBool(mixed $value): bool
    {
        return is_bool($value);
    }

    public static function ruleInt(mixed $value): bool
    {
        return is_int($value);
    }

    public static function ruleNum(mixed $value): bool
    {
        return is_numeric($value);
    }

    public static function ruleRange(mixed $value, int $low, int $high): bool
    {
        return ($low <= $value) && ($value <= $high);
    }

    public static function ruleString(mixed $value): bool
    {
        return is_string($value);
    }
}
