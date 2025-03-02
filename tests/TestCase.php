<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Tonysm\ImportmapLaravel\ImportmapLaravelServiceProvider;
use Tonysm\ImportmapLaravel\Manifest;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('importmap.manifest_location_path', __DIR__.'/stubs/public/.importmap-manifest.json');

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Tonysm\\ImportmapLaravel\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        if (File::exists($stubManifest = Manifest::path())) {
            File::delete($stubManifest);
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            ImportmapLaravelServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.url', 'http://localhost');

        /*
        $migration = include __DIR__.'/../database/migrations/create_importmap-laravel_table.php.stub';
        $migration->up();
        */
    }
}
