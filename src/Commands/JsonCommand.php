<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class JsonCommand extends Command
{
    public $signature = 'importmap:json';

    public $description = 'Displays the generated importmaps JSON based on your current pins.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
