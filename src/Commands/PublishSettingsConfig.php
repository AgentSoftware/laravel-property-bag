<?php

namespace LaravelPropertyBag\Commands;

use Illuminate\Support\Facades\App;
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
    public function handle(): int
    {
        $resource = $this->argument('resource');

        if (! is_string($resource) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $resource) !== 1) {
            $this->error('The resource argument must be a valid identifier (letters, numbers, and underscores; not starting with a number).');

            return self::FAILURE;
        }

        $this->makeDir('Settings');

        $namespace = NameResolver::getAppNamespace().'Settings';

        $resourceName = ucfirst($resource);

        $this->writeConfig($namespace, $resourceName);

        $this->info("{$resourceName} settings file successfully created!");

        return self::SUCCESS;
    }

    /**
     * Write the settings file into the settings folder.
     */
    protected function writeConfig(string $namespace, string $resourceName): void
    {
        $stub = $this->readStub(__DIR__.'/../Stubs/ResourceConfig.php');

        $stub = $this->replace('{{Namespace}}', $namespace, $stub);

        $name = $resourceName.'Settings';

        $stub = $this->replace('{{ClassName}}', $name, $stub);

        file_put_contents(
            App::basePath("app/Settings/{$name}.php"),
            $stub
        );
    }
}
