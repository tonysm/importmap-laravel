<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Collection;

class Importmap
{
    private Collection $packages;

    public function __construct(private ?string $rootPath = null)
    {
        $this->rootPath = rtrim($this->rootPath ?: base_path(), '/');
        $this->packages = collect();
    }

    public function pin(string $name, ?string $to = null)
    {
        $this->packages->add(new MappedFile($name, path: $to ?: "js/{$name}.js"));
    }

    public function asArray(callable $assetResolver): array
    {
        return [
            'imports' => $this->resolveAssetPaths($this->packages, $assetResolver),
        ];
    }

    private function resolveAssetPaths(Collection $paths, callable $assetResolver): array
    {
        return $paths->mapWithKeys(function (MappedFile $mapping) use ($assetResolver) {
            return [$mapping->name => $assetResolver($mapping->path)];
        })->all();
    }
}
