<?php

namespace Tonysm\ImportmapLaravel\View\Components;

use Illuminate\View\Component;
use Tonysm\ImportmapLaravel\AssetResolver;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class Tags extends Component
{
    public function __construct(public string $entrypoint = 'app')
    {
    }

    public function render()
    {
        $resolver = new AssetResolver();

        return view("importmap::tags", [
            'importmaps' => Importmap::asArray($resolver),
            'preloadedModules' => Importmap::preloadedModulePaths($resolver),
        ]);
    }
}
