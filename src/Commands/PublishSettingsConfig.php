<?php

namespace LaravelPropertyBag\Commands;

use LaravelPropertyBag\Helpers\NameResolver;

class PublishSettingsConfig extends PbagCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pbag:make {resource}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a settings config file for a resource.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->makeDir('Settings');

        $namespace = NameResolver::getAppNamespace().'Settings';

        $resourceName = ucfirst($this->argument('resource'));

        $this->writeConfig($namespace, $resourceName);

        $this->info("{$resourceName} settings file successfully created!");
    }

    /**
     * Write the settings file into the settings folder.
     */
    protected function writeConfig(string $namespace, string $resourceName): void
    {
        $stub = file_get_contents(
            __DIR__.'/../Stubs/ResourceConfig.php'
        );

        $stub = $this->replace('{{Namespace}}', $namespace, $stub);

        $name = $resourceName.'Settings';

        $stub = $this->replace('{{ClassName}}', $name, $stub);

        file_put_contents(
            base_path("app/Settings/{$name}.php"),
            $stub
        );
    }
}
