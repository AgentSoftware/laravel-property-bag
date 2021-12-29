<?php

namespace LaravelPropertyBag\Helpers;

use Illuminate\Container\Container;

class NameResolver
{
    /**
     * Get the app namespace from the container.
     *
     * @return string
     */
    public static function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

    /**
     * @return string|null
     */
    private static function getConfigNamespace()
    {
        return config('property_bag.namespace');
    }

    /**
     * Make config file name for resource.
     *
     * @param string $resourceName
     *
     * @return string
     */
    public static function makeConfigFileName($resourceName)
    {
        if ($namespace = static::getConfigNamespace()) {
            return $namespace.'\\'.$resourceName.'Settings';
        }

        $appNamespace = static::getAppNamespace();

        return $appNamespace.'Settings\\'.$resourceName.'Settings';
    }

    /**
     * Make rules file name.
     *
     * @return string
     */
    public static function makeRulesFileName()
    {
        if ($namespace = static::getConfigNamespace()) {
            return $namespace.'\\Resources\\Rules';
        }

        $appNamespace = static::getAppNamespace();

        return $appNamespace.'Settings\\Resources\\Rules';
    }
}
