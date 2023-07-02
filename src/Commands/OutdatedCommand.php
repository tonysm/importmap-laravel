<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Tonysm\ImportmapLaravel\Npm;
use Tonysm\ImportmapLaravel\OutdatedPackage;

class OutdatedCommand extends Command
{
    public $signature = 'importmap:outdated';

    public $description = 'Checks for outdated packages.';

    public function handle(Npm $npm): int
    {
        $outdatedPackages = $npm->outdatedPackages();

        if ($outdatedPackages->isEmpty()) {
            $this->info('No outdated packages found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Package', 'Current', 'Latest'],
            $outdatedPackages
                ->map(fn (OutdatedPackage $package) => [$package->name, $package->currentVersion, $package->latestVersion ?: $package->error])
                ->all(),
        );

        $this->newLine();

        $this->error(sprintf(
            '%d outdated %s found.',
            $outdatedPackages->count(),
            Str::plural('package', $outdatedPackages->count()),
        ));

        return self::FAILURE;
    }
}
