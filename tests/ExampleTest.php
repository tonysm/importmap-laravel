<?php

use Illuminate\Support\Arr;
use Tonysm\ImportmapLaravel\Importmap;

beforeEach(function () {
    $this->map = new Importmap(rootPath: __DIR__ . '/stubs/');

    $this->map->pin("app");
    $this->map->pin("editor", to: "js/rich_text.js");
    $this->map->pin("md5", to: "https://cdn.skypack.dev/md5");
});

test('local bin with inferred to', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.app'))->toEqual(asset('js/app.js'));
});

test('local pin with explicit to', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.editor'))->toEqual(asset('js/rich_text.js'));
});

test('remote pin works', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.md5'))->toEqual('https://cdn.skypack.dev/md5');
});
