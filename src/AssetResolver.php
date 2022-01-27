<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class AssetResolver
{
    public function __invoke(string $fileRelativePath)
    {
        // Production should be using the `importmap:optimize`, which
        // already adds the file digest to the file name itself, so
        // we don't have to worry about other environments here.

        if (! App::environment('local')) {
            return asset($fileRelativePath);
        }

        if (! File::exists($absolutePath = Importmap::getRootPath() . '/resources/' . trim($fileRelativePath, '/'))) {
            return asset($fileRelativePath);
        }

        return asset($fileRelativePath) . '?digest=' . (new FileDigest())($absolutePath);
    }
}
