<?php

use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Importmap;

beforeEach(function () {
    $this->map = $this->instance(Importmap::class, new Importmap($this->rootPath = __DIR__ . '/stubs'));

    $this->map->pin("app");
    $this->map->pin("md5", to: "https://cdn.skypack.dev/md5", preload: true);
    $this->map->pin("my_lib", to: "vendor/nova/my_lib.js", preload: true);

    if (File::isDirectory($this->distPath = $this->rootPath . '/public/dist/')) {
        File::cleanDirectory($this->distPath);
    }
});

test('optimize command generates copies files to public/dist folder', function () {
    $this->artisan('importmap:optimize')
        ->expectsOutput('    copied js/app.js to dist/js/app-da39a3ee5e6b4b0d3255bfef95601890afd80709.js');

    expect(File::exists($this->rootPath . '/public/.importmap-manifest.json'))->toBeTrue();
});

test('uses the generated importmap-manifest.json when that is available', function () {
    File::put($this->map->rootPath . '/public/.importmap-manifest.json', json_encode($imports = [
        ['module' => 'app', 'path' => 'http://example.com/app.js', 'preload' => false],
        ['module' => 'md5', 'path' => 'http://example.com/md5.js', 'preload' => true],
    ], JSON_PRETTY_PRINT));

    expect($this->map->asArray(fn ($file) => $file))->toEqual([
        'imports' => [
            'app' => 'http://example.com/app.js',
            'md5' => 'http://example.com/md5.js',
        ],
    ]);

    expect($this->map->preloadedModulePaths(fn ($file) => $file))->toEqual([
        'md5' => 'http://example.com/md5.js',
    ]);
});
