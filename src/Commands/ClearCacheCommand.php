<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Importmap;

class ClearCacheCommand extends Command
{
    public $signature = 'importmap:clear';

    public $description = 'Clears the optimization caching.';

    public function handle(Importmap $importmap): int
    {
        $this->info('Clearing cached manifest...');

        if (File::exists($manifest = $importmap->rootPath . '/public/importmap-manifest.json')) {
            File::delete($manifest);
        }

        $this->info('Done!');

        return self::SUCCESS;
    }
}
