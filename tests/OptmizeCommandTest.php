<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Importmap;

class OptimizeCommandTest extends TestCase
{
    private string $rootPath;

    private string $distPath;

    private Importmap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->map = $this->instance(Importmap::class, new Importmap($this->rootPath = __DIR__.'/stubs'));

        $this->map->pin('app');
        $this->map->pin('md5', to: 'https://cdn.skypack.dev/md5', preload: true);
        $this->map->pin('my_lib', to: 'vendor/nova/my_lib.js', preload: true);

        if (File::isDirectory($this->distPath = $this->rootPath.'/public/dist/')) {
            File::cleanDirectory($this->distPath);
        }
    }

    /** @test */
    public function optimize_command_generates_copies_files_to_public_dist_folder()
    {
        $this->artisan('importmap:optimize')
            ->expectsOutput('    copied js/app.js to dist/js/app-da39a3ee5e6b4b0d3255bfef95601890afd80709.js');

        $this->assertTrue(File::exists($this->rootPath.'/public/.importmap-manifest.json'));
    }

    /** @test */
    public function uses_the_generated_importmap_manifest_json_when_that_is_available()
    {
        File::put($this->map->rootPath.'/public/.importmap-manifest.json', json_encode($imports = [
            ['module' => 'app', 'path' => 'http://example.com/app.js', 'preload' => false],
            ['module' => 'md5', 'path' => 'http://example.com/md5.js', 'preload' => true],
        ], JSON_PRETTY_PRINT));

        $this->assertEquals([
            'imports' => [
                'app' => 'http://example.com/app.js',
                'md5' => 'http://example.com/md5.js',
            ],
        ], $this->map->asArray(fn ($file) => $file));

        $this->assertEquals([
            'md5' => 'http://example.com/md5.js',
        ], $this->map->preloadedModulePaths(fn ($file) => $file));
    }
}
