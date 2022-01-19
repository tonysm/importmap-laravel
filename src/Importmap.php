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

    public function __construct(private ?string $rootPath = null)
    {
        $this->rootPath = rtrim($this->rootPath ?: base_path(), '/');
        $this->packages = collect();
        $this->directories = collect();
    }

    public function pin(string $name, ?string $to = null)
    {
        $this->packages->add(new MappedFile($name, path: $to ?: "js/{$name}.js"));
    }

    public function pinAllFrom(string $dir, ?string $under = null, ?string $to = null)
    {
        $this->directories->add(new MappedDirectory($dir, $under, $to));
    }

    public function asArray(callable $assetResolver): array
    {
        return [
            'imports' => $this->resolveAssetPaths($this->expandPackagesAndDirectories(), $assetResolver),
        ];
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

                    return new MappedFile($moduleName, $modulePath);
                });
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
            $mapping->path ?: $mapping->under,
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
