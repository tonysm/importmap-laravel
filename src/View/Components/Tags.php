<?php

namespace Tonysm\ImportmapLaravel\View\Components;

use Illuminate\View\Component;
use Tonysm\ImportmapLaravel\AssetResolver;
use Tonysm\ImportmapLaravel\Facades\Importmap as ImportmapFacade;
use Tonysm\ImportmapLaravel\Importmap;

class Tags extends Component
{
    public function __construct(
        public string $entrypoint = 'app',
        public ?string $nonce = null,
        public ?Importmap $importmap = null,
    ) {
    }

    public function render()
    {
        $resolver = new AssetResolver();

        return view('importmap::tags', [
            'importmaps' => $this->importmap?->asArray($resolver) ?? ImportmapFacade::asArray($resolver),
            'preloadedModules' => $this->importmap?->preloadedModulePaths($resolver) ?? ImportmapFacade::preloadedModulePaths($resolver),
        ]);
    }
}
