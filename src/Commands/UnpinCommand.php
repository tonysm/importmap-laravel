<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class UnpinCommand extends Command
{
    public $signature = 'importmap:unpin';

    public $description = 'Removes pinned JavaScript dependencies.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
