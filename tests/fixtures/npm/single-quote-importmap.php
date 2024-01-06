<?php

use Tonysm\ImportmapLaravel\Facades\Importmap;

Importmap::pin('md5', to: 'https://cdn.skypack.dev/md5', preload: true);
Importmap::pin('not_there', to: 'nowhere.js', preload: false);
