<?php

namespace Tonysm\ImportmapLaravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tonysm\ImportmapLaravel\Commands\ImportmapLaravelCommand;

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
            ->name('importmap-laravel')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_importmap-laravel_table')
            ->hasCommand(ImportmapLaravelCommand::class);
    }
}
