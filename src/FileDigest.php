<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\File;

class FileDigest
{
    public function __invoke(string $absolutePath)
    {
        return sha1(File::get($absolutePath));
    }
}
