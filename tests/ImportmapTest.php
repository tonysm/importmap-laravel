<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Support\Arr;
use Tonysm\ImportmapLaravel\Importmap;

class ImportmapTest extends TestCase
{
    private Importmap $map;

    protected function setUp(): void
    {
        parent::setUp();

        $this->map = new Importmap(rootPath: __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR);

        $this->map->pin('app', preload: false);
        $this->map->pin('editor', to: 'js/rich_text.js', preload: false);
        $this->map->pin('not_there', to: 'js/nowhere.js', preload: false);
        $this->map->pin('md5', to: 'https://cdn.skypack.dev/md5', preload: true);

        $this->map->pinAllFrom('resources/js/controllers', under: 'controllers', to: 'js/controllers', preload: true);
        $this->map->pinAllFrom('resources/js/spina/controllers', under: 'controllers/spina', to: 'js/controllers/spina', preload: true);
        $this->map->pinAllFrom('resources/js/spina/controllers', under: 'controllers/spina', to: 'js/spina/controllers', preload: true);
        $this->map->pinAllFrom('resources/js/helpers', under: 'helpers', to: 'js/helpers', preload: true);
        $this->map->pinAllFrom('public/vendor/nova/', preload: true);
        $this->map->pinAllFrom('resources/js/libs', to: 'js/libs');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function local_bin_with_inferred_to(): void
    {
        $this->assertEquals(asset('js/app.js'), Arr::get($this->map->asArray('asset'), 'imports.app'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function local_pin_with_explicit_to(): void
    {
        $this->assertEquals(asset('js/rich_text.js'), Arr::get($this->map->asArray('asset'), 'imports.editor'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function remote_pin_works(): void
    {
        $this->assertEquals('https://cdn.skypack.dev/md5', Arr::get($this->map->asArray('asset'), 'imports.md5'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function directory_pin_mounted_under_matching_subdir_maps_all_files(): void
    {
        $this->assertEquals(asset('js/controllers/hello_controller.js'), Arr::get($this->map->asArray('asset'), 'imports.controllers/hello_controller'));
        $this->assertEquals(asset('js/controllers/utilities/md5_controller.js'), Arr::get($this->map->asArray('asset'), 'imports.controllers/utilities/md5_controller'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function vendor_directory_inside_pinned_folder_is_ignored(): void
    {
        $this->assertNull(Arr::get($this->map->asArray('asset'), 'imports.vendor/alpine'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function directory_pin_mounted_under_matching_subdir_maps_index_as_root(): void
    {
        $this->assertEquals(asset('js/controllers/index.js'), Arr::get($this->map->asArray('asset'), 'imports.controllers'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function directory_pin_mounted_under_matching_subdir_maps_index_as_root_at_second_depth(): void
    {
        $this->assertEquals(asset('js/helpers/requests/index.js'), Arr::get($this->map->asArray('asset'), 'imports.helpers/requests'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function directory_pin_under_custom_asset_path(): void
    {
        $this->assertEquals(asset('js/spina/controllers/another_controller.js'), Arr::get($this->map->asArray('asset'), 'imports.controllers/spina/another_controller'));
        $this->assertEquals(asset('js/spina/controllers/deeper/again_controller.js'), Arr::get($this->map->asArray('asset'), 'imports.controllers/spina/deeper/again_controller'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function directory_pin_without_path_or_under(): void
    {
        $this->assertEquals(asset('my_lib.js'), Arr::get($this->map->asArray('asset'), 'imports.my_lib'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function preload_modules_are_included_in_preload_tags(): void
    {
        $preloadingModulePaths = json_encode($this->map->preloadedModulePaths('asset'));

        $this->assertStringContainsString('md5', $preloadingModulePaths);
        $this->assertStringContainsString('hello_controller', $preloadingModulePaths);
        $this->assertStringNotContainsString('not_there', $preloadingModulePaths);
        $this->assertStringNotContainsString('app', $preloadingModulePaths);
    }
}
