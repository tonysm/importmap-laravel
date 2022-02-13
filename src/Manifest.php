<?php

namespace Tonysm\ImportmapLaravel;

class Manifest
{
    public static function path(): string
    {
        return config('importmap.manifest_location_path');
    }

    public static function filename(): string
    {
        return basename(static::path());
    }
}
