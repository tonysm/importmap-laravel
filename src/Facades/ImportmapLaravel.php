<?php

namespace Tonysm\ImportmapLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tonysm\ImportmapLaravel\ImportmapLaravel
 */
class ImportmapLaravel extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'importmap-laravel';
    }
}
