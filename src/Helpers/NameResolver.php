<?php

namespace LaravelPropertyBag\Helpers;

class NameResolver
{
    /**
     * Get the app namespace from the container.
     */
    public static function getAppNamespace(): string
    {
        return app()->getNamespace();
    }

    private static function getConfigNamespace(): ?string
    {
        // config() is statically typed to return mixed; the ?string return type here
        // is enforced natively by PHP at runtime, so a misconfigured non-string/
        // non-null value already fails fast with a TypeError rather than being
        // silently coerced.
        // @phpstan-ignore return.type
        return config('property_bag.namespace');
    }

    /**
     * Make config file name for resource.
     */
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

    /**
     * Make rules file name.
     */
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
