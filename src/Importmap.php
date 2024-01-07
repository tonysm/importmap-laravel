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
        $this->rootPath = rtrim($this->rootPath ?: base_path(), DIRECTORY_SEPARATOR);
        $this->packages = collect();
        $this->directories = collect();
    }

    public function pin(string $name, ?string $to = null, bool $preload = true)
    {
        $this->packages->add(new MappedFile($name, path: $to ?: "js/{$name}.js", preload: $preload));
    }

    public function pinAllFrom(string $dir, ?string $under = null, ?string $to = null, bool $preload = true)
    {
        $this->directories->add(new MappedDirectory($dir, $under, $to, $preload));
    }

    public function preloadedModulePaths(callable $assetResolver): array
    {
        if ($this->hasManifest()) {
            return $this->resolvePreloadedModulesFromManifest($assetResolver);
        }

        return $this->resolveAssetPaths($this->expandPreloadingPackagesAndDirectories(), $assetResolver);
    }

    public function asArray(callable $assetResolver): array
    {
        if ($this->hasManifest()) {
            return $this->resolveImportsFromManifest($assetResolver);
        }

        return [
            'imports' => $this->resolveAssetPaths($this->expandPackagesAndDirectories(), $assetResolver),
        ];
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getFileAbsolutePath(string $relativePath): string
    {
        return $this->rootPath.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function hasManifest(): bool
    {
        return File::exists($this->manifestPath());
    }

    private function manifestPath(): string
    {
        return Manifest::path();
    }

    private function resolvePreloadedModulesFromManifest($assetResolver): array
    {
        return collect(json_decode(File::get($this->manifestPath()), true))
            ->filter(fn (array $json) => $json['preload'])
            ->mapWithKeys(fn (array $json) => [$json['module'] => $assetResolver($json['path'])])
            ->all();
    }

    private function resolveImportsFromManifest($assetResolver): array
    {
        return [
            'imports' => collect(json_decode(File::get($this->manifestPath()), true))
                ->mapWithKeys(fn (array $json) => [$json['module'] => $assetResolver($json['path'])])
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

                    // We're ignoring anything that starts with `vendor`, as that's probably
                    // being mapped directly as a result of pinning with a --download flag.
                    if (str_starts_with($moduleFilename, 'vendor')) {
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
            return str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $this->rootPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
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
        return trim(Str::after($fileAbsolutePath, $folderAbsolutePath), DIRECTORY_SEPARATOR);
    }

    private function moduleNameFrom(string $moduleFileName, MappedDirectory $mapping): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', implode('/', array_filter([
            $mapping->under,
            preg_replace('#([\\\/]?index)?\.jsm?$#', '', $moduleFileName),
        ])));
    }

    private function modulePathFrom(string $moduleFilename, MappedDirectory $mapping): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', implode('/', array_filter([
            rtrim($mapping->path ?: $mapping->under, DIRECTORY_SEPARATOR.'/'),
            $moduleFilename,
        ])));
    }

    private function resolveAssetPaths(Collection $paths, callable $assetResolver): array
    {
        return $paths->mapWithKeys(function (MappedFile $mapping) use ($assetResolver) {
            return [$mapping->name => $assetResolver($mapping->path)];
        })->all();
    }
}
