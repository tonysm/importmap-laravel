<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\File;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class AssetResolver
{
    public function __invoke(string $fileRelativePath)
    {
        if (str_starts_with($fileRelativePath, 'vendor/') && File::exists($absolutePath = public_path($fileRelativePath))) {
            return asset($fileRelativePath).'?digest='.(new FileDigest())($absolutePath);
        }

        if (! File::exists($absolutePath = Importmap::getFileAbsolutePath('/resources/'.trim($fileRelativePath, '/')))) {
            return asset($fileRelativePath);
        }

        return asset($fileRelativePath).'?digest='.(new FileDigest())($absolutePath);
    }
}
