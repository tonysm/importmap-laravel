<?php

use Tonysm\ImportmapLaravel\Facades\Importmap;

Importmap::pin('is-svg', to: 'https://cdn.skypack.dev/is-svg@3.0.0', preload: true);
Importmap::pin('lodash', to: '/js/vendor/lodash.js'); // @4.17.12
