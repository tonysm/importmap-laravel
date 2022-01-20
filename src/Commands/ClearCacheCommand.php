<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class ClearCacheCommand extends Command
{
    public $signature = 'importmap:clear';

    public $description = 'Clears the optimization caching.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
