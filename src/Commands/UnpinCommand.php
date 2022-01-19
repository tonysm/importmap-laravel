<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Tonysm\ImportmapLaravel\Packager;

class UnpinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        importmap:unpin
        {--from-env=production : The CDN environment}
        {--download : If used, the dependency will be downloaded to be checked in under version control instead of relying on CDNs.}
        {--from=jspm : The CDN.}
        {packages*}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes a pinned dependency.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Packager $packager)
    {
        $packages = Arr::wrap($this->argument('packages'));

        if ($imports = $packager->import($packages, $this->option('from-env'), $this->option('from'))) {
            $imports->each(function (string $url, string $package) use ($packager) {
                if ($packager->packaged($package)) {
                    if ($this->option('download')) {
                        $this->info(sprintf('Unpinning and removing "%s"', $package));
                    } else {
                        $this->info(sprintf('Unpinning "%s"', $package));
                    }

                    $packager->remove($package);
                }
            });

            return self::SUCCESS;
        }

        $this->error(sprintf(
            "Couldn't find any packages in %s on %s",
            implode(', ', $packages),
            $this->option('from'),
        ));

        return self::FAILURE;
    }
}
