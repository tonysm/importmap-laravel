<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class PinCommand extends Command
{
    public $signature = 'importmap:pin';

    public $description = 'Pin JavaScript dependencies.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
