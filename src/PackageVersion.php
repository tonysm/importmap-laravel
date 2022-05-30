<?php

namespace Tonysm\ImportmapLaravel;

class PackageVersion
{
    public function __construct(
        public string $name,
        public string $version,
    ) {
    }
}
