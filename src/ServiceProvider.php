<?php

namespace LaravelPropertyBag;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider as BaseProvider;
use LaravelPropertyBag\Commands\PublishRulesFile;
use LaravelPropertyBag\Commands\PublishSettingsConfig;

class ServiceProvider extends BaseProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/property_bag.php', 'property_bag');

        $this->commands([
            PublishSettingsConfig::class,
            PublishRulesFile::class,
        ]);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/property_bag.php' => App::configPath('property_bag.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/Migrations/' => App::databasePath('migrations'),
        ], 'migrations');
    }
}
