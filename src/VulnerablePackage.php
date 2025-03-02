<?php

namespace Tonysm\ImportmapLaravel;

class VulnerablePackage
{
    public function __construct(
        public string $name,
        public string $severity,
        public string $vulnerableVersions,
        public string $vulnerability,
    ) {}
}
