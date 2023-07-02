<?php

namespace Tonysm\ImportmapLaravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tonysm\ImportmapLaravel\View\Components;

class ImportmapLaravelServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('importmap')
            ->hasConfigFile()
            ->hasViews()
            ->hasViewComponent('importmap', Components\Tags::class)
            ->hasCommand(Commands\InstallCommand::class)
            ->hasCommand(Commands\OptimizeCommand::class)
            ->hasCommand(Commands\ClearCacheCommand::class)
            ->hasCommand(Commands\JsonCommand::class)
            ->hasCommand(Commands\PinCommand::class)
            ->hasCommand(Commands\UnpinCommand::class)
            ->hasCommand(Commands\OutdatedCommand::class)
            ->hasCommand(Commands\AuditCommand::class)
            ->hasCommand(Commands\PackagesCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->scoped(Importmap::class, function () {
            return new Importmap();
        });

        $this->app->bind('importmap-laravel', Importmap::class);
    }

    public function packageBooted()
    {
        if (file_exists(base_path('routes/importmap.php'))) {
            require base_path('routes/importmap.php');
        }
    }
}
