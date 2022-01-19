<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class ImportmapLaravelCommand extends Command
{
    public $signature = 'importmap-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
