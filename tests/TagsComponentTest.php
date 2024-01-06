<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Importmap;

class TagsComponentTest extends TestCase
{
    use InteractsWithViews;

    private Importmap $map;

    private string $rootPath;

    private string $distPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->map = $this->instance(Importmap::class, new Importmap($this->rootPath = __DIR__.'/stubs'));

        $this->map->pin('app');
        $this->map->pin('md5', to: 'https://cdn.skypack.dev/md5', preload: true);

        if (File::isDirectory($this->distPath = $this->rootPath.'/public/dist/')) {
            File::cleanDirectory($this->distPath);
        }
    }

    /** @test */
    public function generates_tags_without_nonce()
    {
        $this->blade('<x-importmap::tags />')
            ->assertSee('<link rel="modulepreload" href="https://cdn.skypack.dev/md5" />', escape: false);
    }

    /** @test */
    public function uses_given_csp_nonce()
    {
        $this->blade('<x-importmap::tags nonce="h3ll0" />')
            ->assertSee('<link rel="modulepreload" href="https://cdn.skypack.dev/md5" nonce="h3ll0" />', escape: false);
    }

    /** @test */
    public function uses_custom_map()
    {
        $importmap = new Importmap();
        $importmap->pin('foo', preload: true);
        $importmap->pin('bar', preload: true);

        $this->blade('<x-importmap::tags :importmap="$importmap" />', ['importmap' => $importmap])
            ->assertSee('<link rel="modulepreload" href="'.asset('js/foo.js').'" />', escape: false)
            ->assertSee('<link rel="modulepreload" href="'.asset('js/bar.js').'" />', escape: false)
            ->assertDontSee('<link rel="modulepreload" href="https://cdn.skypack.dev/md5" />', escape: false);
    }
}
