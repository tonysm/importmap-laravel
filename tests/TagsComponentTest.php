<?php

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Importmap;

uses(InteractsWithViews::class);

beforeEach(function () {
    $this->map = $this->instance(Importmap::class, new Importmap($this->rootPath = __DIR__ . '/stubs'));

    $this->map->pin("app");
    $this->map->pin("md5", to: "https://cdn.skypack.dev/md5", preload: true);

    if (File::isDirectory($this->distPath = $this->rootPath . '/public/dist/')) {
        File::cleanDirectory($this->distPath);
    }
});

it('generates tags without nonce', function () {
    $this->blade('<x-importmap-tags />')
        ->assertSee('<link rel="modulepreload" href="https://cdn.skypack.dev/md5" />', escape: false)
        ->assertDontSee('<script type="esms-options"', escape: false)
        ->assertSee('<script async src="https://ga.jspm.io/npm:es-module-shims@1.5.8/dist/es-module-shims.js" data-turbo-track="reload"></script>', escape: false);
});

it('uses given CSP nonce', function () {
    $this->blade('<x-importmap-tags nonce="h3ll0" />')
        ->assertSee('<link rel="modulepreload" href="https://cdn.skypack.dev/md5" nonce="h3ll0" />', escape: false)
        ->assertSee('<script type="esms-options" nonce="h3ll0">{"nonce":"h3ll0"}</script>', escape: false)
        ->assertSee('<script async src="https://ga.jspm.io/npm:es-module-shims@1.5.8/dist/es-module-shims.js" data-turbo-track="reload" nonce="h3ll0"></script>', escape: false);
});
