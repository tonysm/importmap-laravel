<?php

use Illuminate\Support\Arr;
use Tonysm\ImportmapLaravel\Importmap;

beforeEach(function () {
    $this->map = new Importmap(rootPath: __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR);

    $this->map->pin("app");
    $this->map->pin("editor", to: "js/rich_text.js");
    $this->map->pin("md5", to: "https://cdn.skypack.dev/md5", preload: true);

    $this->map->pinAllFrom("resources/js/controllers", under: "controllers", to: "js/controllers", preload: true);
    $this->map->pinAllFrom("resources/js/spina/controllers", under: "controllers/spina", to: "js/controllers/spina", preload: true);
    $this->map->pinAllFrom("resources/js/spina/controllers", under: "controllers/spina", to: "js/spina/controllers", preload: true);
    $this->map->pinAllFrom("resources/js/helpers", under: "helpers", to: "js/helpers", preload: true);
    $this->map->pinAllFrom("public/vendor/nova/", preload: true);
    $this->map->pinAllFrom("resources/js/libs", to: "js/libs");
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

test('directory pin mounted under matchin subdir maps all files', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.controllers/hello_controller'))->toEqual(asset('js/controllers/hello_controller.js'));
    expect(Arr::get($this->map->asArray('asset'), 'imports.controllers/utilities/md5_controller'))->toEqual(asset('js/controllers/utilities/md5_controller.js'));
});

test('vendor directory inside pinned folder is ignored', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.vendor/alpine'))->toBeNull();
});

test('directory pin mounted under matching subdir maps index as root', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.controllers'))->toEqual(asset('js/controllers/index.js'));
});

test('directory pin mounted under matching subdir maps index as root at second depth', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.helpers/requests'))->toEqual(asset('js/helpers/requests/index.js'));
});

test('directory pin under custom asset path', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.controllers/spina/another_controller'))->toEqual(asset('js/spina/controllers/another_controller.js'));
    expect(Arr::get($this->map->asArray('asset'), 'imports.controllers/spina/deeper/again_controller'))->toEqual(asset('js/spina/controllers/deeper/again_controller.js'));
});

test('directory pin without path or under', function () {
    expect(Arr::get($this->map->asArray('asset'), 'imports.my_lib'))->toEqual(asset('my_lib.js'));
});

test('preload modules are included in preload tags', function () {
    $preloadingModulePaths = json_encode($this->map->preloadedModulePaths('asset'));

    expect($preloadingModulePaths)->toContain('md5');
    expect($preloadingModulePaths)->toContain('hello_controller');
    expect($preloadingModulePaths)->not->toContain('app');
});
