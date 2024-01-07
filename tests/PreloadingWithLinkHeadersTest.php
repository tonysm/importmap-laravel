<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tonysm\ImportmapLaravel\AssetResolver;
use Tonysm\ImportmapLaravel\Http\Middleware\AddLinkHeadersForPreloadedPins;
use Tonysm\ImportmapLaravel\Importmap;

class PreloadingWithLinkHeadersTest extends TestCase
{
    /** @test */
    public function doesnt_set_link_header_when_no_pins_are_preloaded(): void
    {
        $this->swap(Importmap::class, $map = new Importmap(rootPath: __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR));

        $map->pin('app', preload: false);
        $map->pin('editor', to: 'js/rich_text.js', preload: false);
        $map->pinAllFrom('resources/js/', under: 'controllers', to: 'js/', preload: false);

        $response = (new AddLinkHeadersForPreloadedPins())->handle(new Request(), function () {
            return new Response('Hello World');
        });

        $this->assertNull($response->headers->get('Link'));
    }

    /** @test */
    public function sets_link_header_when_pins_are_preloaded(): void
    {
        $this->swap(Importmap::class, $map = new Importmap(rootPath: __DIR__.DIRECTORY_SEPARATOR.'stubs'.DIRECTORY_SEPARATOR));

        $map->pin('app', preload: true);
        $map->pin('editor', to: 'js/rich_text.js', preload: false);
        $map->pinAllFrom('resources/js/', under: 'controllers', to: 'js/', preload: true);

        $resolver = new class () extends AssetResolver {
            public function __invoke($module)
            {
                return 'http://localhost/'.str_replace(['.js'], ['-123123.js'], $module);
            }
        };

        $response = (new AddLinkHeadersForPreloadedPins($resolver))->handle(new Request(), function () {
            return new Response('Hello World');
        });

        $this->assertEquals(
            '<http://localhost/js/app-123123.js>; rel="modulepreload", <http://localhost/js/app-123123.js>; rel="modulepreload", <http://localhost/js/controllers/hello_controller-123123.js>; rel="modulepreload", <http://localhost/js/controllers/index-123123.js>; rel="modulepreload", <http://localhost/js/controllers/utilities/md5_controller-123123.js>; rel="modulepreload", <http://localhost/js/helpers/requests/index-123123.js>; rel="modulepreload", <http://localhost/js/libs/vendor/alpine-123123.js>; rel="modulepreload", <http://localhost/js/spina/controllers/another_controller-123123.js>; rel="modulepreload", <http://localhost/js/spina/controllers/deeper/again_controller-123123.js>; rel="modulepreload"',
            $response->headers->get('Link'),
        );
    }
}
