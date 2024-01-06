<?php

namespace Tonysm\ImportmapLaravel\Http\Middleware;

use Tonysm\ImportmapLaravel\AssetResolver;
use Tonysm\ImportmapLaravel\Facades\Importmap;

class AddLinkHeadersForPreloadedPins
{
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
            $resolver = new AssetResolver();

            if ($preloaded = Importmap::preloadedModulePaths($resolver)) {
                $response->header('Link', collect($preloaded)
                    ->map(fn ($url) => "<{$url}>; rel=\"modulepreload\"")
                    ->join(', '));
            }
        });
    }
}
