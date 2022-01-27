<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class AssetResolver
{
    public function __invoke(string $fileRelativePath)
    {
        if (! File::exists($absolutePath = Importmap::getRootPath() . '/resources/' . trim($fileRelativePath, '/'))) {
            return asset($fileRelativePath);
        }

        return asset($fileRelativePath) . '?digest=' . (new FileDigest())($absolutePath);
    }
}
