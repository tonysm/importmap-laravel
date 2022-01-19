<?php

use Illuminate\Support\Arr;
use Tonysm\ImportmapLaravel\Importmap;

beforeEach(function () {
    $this->map = new Importmap(rootPath: __DIR__ . '/stubs/');

    $this->map->pin("app");
});

it('local bin with inferred to', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.app'))->toEqual(asset('js/app.js'));
});
