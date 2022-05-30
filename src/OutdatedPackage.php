<?php

namespace Tonysm\ImportmapLaravel;

class OutdatedPackage
{
    public function __construct(
        public string $name,
        public string $currentVersion,
        public ?string $latestVersion = null,
        public ?string $error = null,
    ) {
        //
    }
}
