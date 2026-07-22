<?php

namespace LaravelPropertyBag\Commands;

use Illuminate\Support\Facades\App;
use LaravelPropertyBag\Helpers\NameResolver;

class PublishRulesFile extends PbagCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pbag:rules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make user-defined rules file in Settings/Resources.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->makeDir('Settings');

        $this->makeDir('Settings/Resources');

        $namespace = NameResolver::getAppNamespace().'Settings\\Resources';

        if (! $this->writeRulesFile($namespace)) {
            $this->error('Unable to write rules file.');

            return self::FAILURE;
        }

        $this->info('Rules file successfully created!');

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
    protected function writeRulesFile(string $namespace): bool
    {
        $stub = $this->readStub(__DIR__.'/../Stubs/Rules.php');

        $stub = $this->replace('{{Namespace}}', $namespace, $stub);

        return @file_put_contents(
            App::basePath('app/Settings/Resources/Rules.php'),
            $stub
        ) !== false;
    }
}
