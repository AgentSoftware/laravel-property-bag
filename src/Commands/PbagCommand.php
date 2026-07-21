<?php

namespace LaravelPropertyBag\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PbagCommand extends Command
{
    /**
     * Make directory if it doesn't already exist.
     */
    protected function makeDir(string $dir): void
    {
        $dirPath = base_path('app/'.ltrim($dir, '/'));

        if (! File::exists($dirPath)) {
            File::makeDirectory($dirPath);
        }
    }

    /**
     * Replace mustache with replacement in file.
     */
    protected function replace(string $mustache, string $replacement, string $file): string
    {
        return str_replace($mustache, $replacement, $file);
    }

    /**
     * Read a bundled stub file's contents.
     *
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
