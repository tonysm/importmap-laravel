<?php

namespace Tonysm\ImportmapLaravel;

class MappedDirectory
{
    public function __construct(public string $dir, public ?string $under = null, public ?string $path = null, public bool $preload = false)
    {
    }
}
