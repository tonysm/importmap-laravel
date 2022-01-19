<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class BuildCommand extends Command
{
    public $signature = 'importmap:build';

    public $description = 'Builds the JavaScript dependencies, generating a manifest so we dont have to recreate the importmaps JSON on the spot (good for Vapor).';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
