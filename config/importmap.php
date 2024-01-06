<?php

return [
    /*
     |------------------------------------------------------------------
     | The path to the location where the manifest file will be created.
     |------------------------------------------------------------------
     |
     | The manifest file will be used to store the optimized JSON file containing the import
     | maps JSON map so we don't have to always generate it on the fly. That manifest is
     | for internal usage only. It will be created by the `importmap:optimize` command.
     |
     */
    'manifest_location_path' => public_path('.importmap-manifest.json'),
];
