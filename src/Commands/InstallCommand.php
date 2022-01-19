<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'importmap:install';

    public $description = 'Installs the package.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
