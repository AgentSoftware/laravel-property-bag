<?php

namespace LaravelPropertyBag\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class PbagCommand extends Command
{
    /**
     * Note: the mkdir() call is @-suppressed so that a failure surfaces as a
     * false return value we can check below, rather than as the uncaught
     * \ErrorException Laravel's default error handler would otherwise throw
     * for the underlying PHP warning.
     *
     * @throws \RuntimeException if the directory cannot be created.
     */
    protected function makeDir(string $dir): void
    {
        $dirPath = App::basePath('app/'.ltrim($dir, '/'));

        if (! File::exists($dirPath) && ! @File::makeDirectory($dirPath)) {
            throw new \RuntimeException("Unable to create directory at {$dirPath}.");
        }
    }

    protected function replace(string $mustache, string $replacement, string $file): string
    {
        return str_replace($mustache, $replacement, $file);
    }

    /**
     * @throws \RuntimeException if the stub file cannot be read.
     */
    protected function readStub(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read stub file at {$path}.");
        }

        return $contents;
    }
}
