<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;
use Tonysm\ImportmapLaravel\Packager;

#[AsCommand('importmap:unpin')]
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
            $imports->each(function (string $_url, string $package) use ($packager) {
                if ($packager->packaged($package)) {
                    $this->info(sprintf('Unpinning and removing "%s"', $package));

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
