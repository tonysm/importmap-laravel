<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Tonysm\ImportmapLaravel\Npm;

class PackagesCommand extends Command
{
    public $signature = 'importmap:packages';

    public $description = 'Displays the importmap packages with version numbers.';

    public function handle(Npm $npm): int
    {
        $npm->packagesWithVersion()->each(fn ($package) => (
            $this->output->writeln(join(' ', $package))
        ));

        return self::SUCCESS;
    }
}
