<?php

namespace LaravelPropertyBag\Helpers;

use Illuminate\Container\Container;

class NameResolver
{
    /**
     * Get the app namespace from the container.
     */
    public static function getAppNamespace(): string
    {
        return Container::getInstance()->getNamespace();
    }

    private static function getConfigNamespace(): ?string
    {
        return config('property_bag.namespace');
    }

    /**
     * Make config file name for resource.
     */
    public static function makeConfigFileName(string $resourceName): string
    {
        if ($namespace = static::getConfigNamespace()) {
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
        if ($namespace = static::getConfigNamespace()) {
            return $namespace.'\\Resources\\Rules';
        }

        $appNamespace = static::getAppNamespace();

        return $appNamespace.'Settings\\Resources\\Rules';
    }
}
