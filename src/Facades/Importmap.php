<?php

namespace Tonysm\ImportmapLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tonysm\ImportmapLaravel\Importmap
 */
class Importmap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'importmap-laravel';
    }
}
