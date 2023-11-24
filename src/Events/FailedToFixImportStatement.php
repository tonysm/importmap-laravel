<?php

namespace Tonysm\ImportmapLaravel\Events;

use SplFileInfo;

class FailedToFixImportStatement
{
    public function __construct(public SplFileInfo $file, public string $importStatement)
    {
        //
    }
}
