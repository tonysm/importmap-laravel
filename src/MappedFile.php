<?php

namespace Tonysm\ImportmapLaravel;

class MappedFile
{
    public function __construct(public string $name, public string $path, public bool $preload) {}
}
