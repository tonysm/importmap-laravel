<?php

return [
    /*
     |-------------------------------------------------------------------
     | Whether to use the shim or not.
     |-------------------------------------------------------------------
     |
     | In some environments, such as when running browser testing, for instance,
     | you may be running on a controller environment so the shim may slow down
     | your tests, so you may prefer to not use the shim in such situations.
     |
     */
    'use_shim' => true,

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
