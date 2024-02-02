<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\View\Compilers\BladeCompiler;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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

        if (app()->environment('local') && app()->runningInConsole()) {
            config()->set('filesystems.links', config('filesystems.links', []) + [
                public_path('js') => resource_path('js'),
            ]);
        }

        $this->configureComponents();
    }

    private function configureComponents()
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            $blade->anonymousComponentPath(__DIR__.'/../resources/views/components', 'importmap');
        });
    }
}
