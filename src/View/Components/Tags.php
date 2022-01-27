<?php

namespace Tonysm\ImportmapLaravel\View\Components;

use Illuminate\View\Component;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class Tags extends Component
{
    public function __construct(public string $entrypoint = 'app')
    {
    }

    public function render()
    {
        return view("importmap::tags", [
            'importmaps' => Importmap::asArray('asset'),
            'preloadedModules' => Importmap::preloadedModulePaths('asset'),
        ]);
    }
}
