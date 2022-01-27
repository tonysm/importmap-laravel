<?php

namespace Tonysm\ImportmapLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void pin(string $name, ?string $to = null, bool $preload = false)
 * @method static void pinAllFrom(string $dir, ?string $under = null, ?string $to = null, bool $preload = false)
 * @method static array asArray(callable $assetResolver)
 * @method static array preloadedModulePaths(callable $assetResolver)
 * @method static string getRootPath()
 *
 * @see \Tonysm\ImportmapLaravel\Importmap
 */
class Importmap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'importmap-laravel';
    }
}
