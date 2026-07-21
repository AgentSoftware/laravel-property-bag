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
}
