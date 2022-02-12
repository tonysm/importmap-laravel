<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

class Importmap
{
    private Collection $packages;
    private Collection $directories;

    public function __construct(public ?string $rootPath = null)
    {
        $this->rootPath = rtrim($this->rootPath ?: base_path(), '/');
        $this->packages = collect();
        $this->directories = collect();
    }

    public function pin(string $name, ?string $to = null, bool $preload = false)
    {
        $this->packages->add(new MappedFile($name, path: $to ?: "js/{$name}.js", preload: $preload));
    }

    public function pinAllFrom(string $dir, ?string $under = null, ?string $to = null, bool $preload = false)
    {
        $this->directories->add(new MappedDirectory($dir, $under, $to, $preload));
    }

    public function preloadedModulePaths(callable $assetResolver): array
    {
        if ($this->hasManifest()) {
            return $this->resolvePreloadedModulesFromManifest();
        }

        return $this->resolveAssetPaths($this->expandPreloadingPackagesAndDirectories(), $assetResolver);
    }

    public function asArray(callable $assetResolver): array
    {
        if ($this->hasManifest()) {
            return $this->resolveImportsFromManifest();
        }

        return [
            'imports' => $this->resolveAssetPaths($this->expandPackagesAndDirectories(), $assetResolver),
        ];
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    private function hasManifest(): bool
    {
        return File::exists($this->manifestPath());
    }

    private function manifestPath(): string
    {
        return $this->rootPath . '/public/.importmap-manifest.json';
    }

    private function resolvePreloadedModulesFromManifest(): array
    {
        return collect(json_decode(File::get($this->manifestPath()), true))
            ->filter(fn (array $json) => $json['preload'])
            ->mapWithKeys(fn (array $json) => [$json['module'] => $json['path']])
            ->all();
    }

    private function resolveImportsFromManifest(): array
    {
        return [
            'imports' => collect(json_decode(File::get($this->manifestPath()), true))
                ->mapWithKeys(fn (array $json) => [$json['module'] => $json['path']])
                ->all(),
        ];
    }

    private function expandPreloadingPackagesAndDirectories(): Collection
    {
        return $this->expandPackagesAndDirectories()
            ->filter(fn (MappedFile $mapping) => $mapping->preload)
            ->values();
    }

    private function expandPackagesAndDirectories(): Collection
    {
        return $this->packages->collect()->merge($this->expandDirectories());
    }

    private function expandDirectories(): Collection
    {
        return $this->directories->flatMap(function (MappedDirectory $mapping) {
            if (! File::isDirectory($absolutePath = $this->absoluteRootOf($mapping->dir))) {
                return [];
            }

            return $this->findJavascriptFilesInTree($absolutePath)
                ->map(function (SplFileInfo $file) use ($mapping, $absolutePath) {
                    $moduleFilename = $this->relativePathFrom($file->getRealPath(), $absolutePath);
                    $moduleName = $this->moduleNameFrom($moduleFilename, $mapping);
                    $modulePath = $this->modulePathFrom($moduleFilename, $mapping);

                    // We're ignoring anything that starts with `vendor/`, as that's probably
                    // being mapped directly as a result of pinning with a --download flag.
                    if (str_starts_with($moduleFilename, 'vendor/')) {
                        return null;
                    }

                    return new MappedFile($moduleName, $modulePath, $mapping->preload);
                })
                ->filter();
        });
    }

    private function absoluteRootOf(string $path): string
    {
        if (Str::startsWith($path, '/')) {
            return $path;
        }

        return $this->rootPath . '/' . $path;
    }

    private function findJavascriptFilesInTree(string $absolutePath): Collection
    {
        $allFiles = File::allFiles($absolutePath);

        return collect($allFiles)
            ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), ['js', 'jsm']))
            ->values();
    }

    private function relativePathFrom(string $fileAbsolutePath, string $folderAbsolutePath)
    {
        return trim(Str::after($fileAbsolutePath, $folderAbsolutePath), '/');
    }

    private function moduleNameFrom(string $moduleFileName, MappedDirectory $mapping): string
    {
        return implode('/', array_filter([
            $mapping->under,
            preg_replace('#(/?index)?\.jsm?$#', '', $moduleFileName),
        ]));
    }

    private function modulePathFrom(string $moduleFilename, MappedDirectory $mapping): string
    {
        return implode('/', array_filter([
            rtrim($mapping->path ?: $mapping->under, '/'),
            $moduleFilename,
        ]));
    }

    private function resolveAssetPaths(Collection $paths, callable $assetResolver): array
    {
        return $paths->mapWithKeys(function (MappedFile $mapping) use ($assetResolver) {
            return [$mapping->name => $assetResolver($mapping->path)];
        })->all();
    }
}
