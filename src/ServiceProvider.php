<?php

namespace LaravelPropertyBag;

use Illuminate\Support\ServiceProvider as BaseProvider;
use LaravelPropertyBag\Commands\PublishRulesFile;
use LaravelPropertyBag\Commands\PublishSettingsConfig;

class ServiceProvider extends BaseProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/property_bag.php', 'property_bag');

        $this->commands([
            PublishSettingsConfig::class,
            PublishRulesFile::class,
        ]);
    }

    /**
     * Register any other events for your application.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/property_bag.php' => config_path('property_bag.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/Migrations/' => database_path('migrations'),
        ], 'migrations');
    }
}
