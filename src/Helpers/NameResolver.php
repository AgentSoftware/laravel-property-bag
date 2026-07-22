<?php

namespace LaravelPropertyBag\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class NameResolver
{
    /**
     * Get the app namespace from the container.
     */
    public static function getAppNamespace(): string
    {
        return App::getNamespace();
    }

    private static function getConfigNamespace(): ?string
    {
        // Config::get() is statically typed to return mixed; the ?string return type here
        // is enforced natively by PHP at runtime, so a misconfigured non-string/
        // non-null value already fails fast with a TypeError rather than being
        // silently coerced.
        // @phpstan-ignore return.type
        return Config::get('property_bag.namespace');
    }

    public static function makeConfigFileName(string $resourceName): string
    {
        // Truthy check intentionally treats an empty-string config value the same
        // as an unset one; self:: is used instead of static:: because the called
        // method is private and never overridden.
        // @phpstan-ignore if.condNotBoolean
        if ($namespace = self::getConfigNamespace()) {
            return $namespace.'\\'.$resourceName.'Settings';
        }

        $appNamespace = static::getAppNamespace();

        return $appNamespace.'Settings\\'.$resourceName.'Settings';
    }

    public static function makeRulesFileName(): string
    {
        // Truthy check intentionally treats an empty-string config value the same
        // as an unset one; self:: is used instead of static:: because the called
        // method is private and never overridden.
        // @phpstan-ignore if.condNotBoolean
        if ($namespace = self::getConfigNamespace()) {
            return $namespace.'\\Resources\\Rules';
        }

        $appNamespace = static::getAppNamespace();

        return $appNamespace.'Settings\\Resources\\Rules';
    }
}
