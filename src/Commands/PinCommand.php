<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Packager;

class PinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        importmap:pin
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
    protected $description = 'Pins a new dependency.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Packager $packager)
    {
        $this->call('importmap:clear');

        $packages = Arr::wrap($this->argument('packages'));

        if ($imports = $packager->import($packages, $this->option('from-env'), $this->option('from'))) {
            $this->importPackages($packager, $imports);

            return Command::SUCCESS;
        }

        $this->error(sprintf("Couldn't find any packages in %s on %s", implode(', ', $packages), $this->option('from')));

        return Command::FAILURE;
    }

    private function importPackages(Packager $packager, Collection $imports): void
    {
        $imports->each(function (string $url, string $package) use ($packager) {
            if ($this->option('download')) {
                $this->info(sprintf(
                    'Pinning "%s" to %s/%s.js via download from %s',
                    $package,
                    $packager->vendorPath,
                    $package,
                    $url,
                ));

                $packager->download($package, $url);

                $pin = $packager->vendoredPinFor($package, $url);
            } else {
                $this->info(sprintf(
                    'Pinning "%s" to %s',
                    $package,
                    $url,
                ));

                $pin = $packager->pinFor($package, $url);
            }

            if ($packager->packaged($package)) {
                // Replace existing pin...
                File::put(
                    base_path($packager->importmapPath),
                    preg_replace($this->pattern($package), $pin, File::get(base_path($packager->importmapPath))),
                );
            } else {
                // Append to file...
                File::append(base_path($packager->importmapPath), "{$pin}\n");
            }
        });
    }

    private function pattern(string $package): string
    {
        return sprintf(
            '#^Importmap::pin\("%s".*$#',
            $package,
        );
    }
}
