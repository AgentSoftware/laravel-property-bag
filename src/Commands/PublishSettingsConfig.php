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

        if (! $this->writeConfig($namespace, $resourceName)) {
            $this->error("Unable to write {$resourceName} settings file.");

            return self::FAILURE;
        }

        $this->info("{$resourceName} settings file successfully created!");

        return self::SUCCESS;
    }

    /**
     * Write the settings file into the settings folder. Returns true on success,
     * false if the write failed (e.g. the target directory is not writable).
     *
     * Note: the write is @-suppressed so that a failure surfaces as a false
     * return value we can check, rather than as the uncaught \ErrorException
     * Laravel's default error handler would otherwise throw for the underlying
     * PHP warning.
     */
    protected function writeConfig(string $namespace, string $resourceName): bool
    {
        $stub = $this->readStub(__DIR__.'/../Stubs/ResourceConfig.php');

        $stub = $this->replace('{{Namespace}}', $namespace, $stub);

        $name = $resourceName.'Settings';

        $stub = $this->replace('{{ClassName}}', $name, $stub);

        return @file_put_contents(
            App::basePath("app/Settings/{$name}.php"),
            $stub
        ) !== false;
    }
}
