<?php

namespace Tonysm\ImportmapLaravel\Http\Middleware;

use Tonysm\ImportmapLaravel\AssetResolver;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class AddLinkHeadersForPreloadedPins
{
    public function __construct(private AssetResolver $assetsResolver = new AssetResolver())
    {
    }

    /**
     * Sets the Link header for preloaded pins.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        return tap($next($request), function ($response) {
            if ($preloaded = Importmap::preloadedModulePaths($this->assetsResolver)) {
                $response->header('Link', collect($preloaded)
                    ->map(fn ($url) => "<{$url}>; rel=\"modulepreload\"")
                    ->join(', '));
            }
        });
    }
}
