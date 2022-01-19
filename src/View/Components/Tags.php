<?php

namespace Tonysm\ImportmapLaravel\View\Components;

use Illuminate\View\Component;

class Tags extends Component
{
    public function render()
    {
        return view("importmap::tags", [
            'entrypoint' => 'app',
            'importmaps' => [
                'imports' => [
                    'app' => asset('js/app.js'),
                    'alpinejs' => 'https://ga.jspm.io/npm:alpinejs@3.8.1/dist/module.esm.js',
                ],
            ],
            'preloadedModules' => [
                'https://ga.jspm.io/npm:alpinejs@3.8.1/dist/module.esm.js',
            ],
        ]);
    }
}
