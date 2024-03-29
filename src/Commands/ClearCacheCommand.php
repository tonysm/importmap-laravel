<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Tonysm\ImportmapLaravel\Importmap;
use Tonysm\ImportmapLaravel\Manifest;

#[AsCommand('importmap:clear')]
class ClearCacheCommand extends Command
{
    public $signature = 'importmap:clear';

    public $description = 'Clears the optimization caching.';

    public function handle(Importmap $importmap): int
    {
        $this->info('Clearing cached manifest...');

        if (File::exists($manifest = Manifest::path())) {
            File::delete($manifest);
        }

        $this->info('Manifest file cleared!');

        return self::SUCCESS;
    }
}
