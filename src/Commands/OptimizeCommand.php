<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Tonysm\ImportmapLaravel\FileDigest;
use Tonysm\ImportmapLaravel\Importmap;
use Tonysm\ImportmapLaravel\Manifest;

#[AsCommand('importmap:optimize')]
class OptimizeCommand extends Command
{
    public $signature = 'importmap:optimize';

    public $description = 'Builds the JavaScript dependencies, generating a manifest so we dont have to recreate the importmaps JSON on the spot (good for Vapor).';

    public function handle(Importmap $importmap): int
    {
        $this->call('importmap:clear');
        $this->info('Copying over the files to a dist folder and generating a digest of them...');

        if ($imports = $importmap->asArray(fn ($file) => $file)) {
            $optimizedImports = collect($imports['imports'])
                ->reject(fn (string $url) => Str::startsWith($url, ['http://', 'https://']))
                ->map(function (string $file) use ($importmap) {
                    $sourceFile = $importmap->rootPath.(str_starts_with(trim($file, '/'), 'vendor/') ? '/public/' : '/resources/').trim($file, '/');
                    $sourceReplacement = $importmap->rootPath.'/public/dist/'.trim($this->digest($file, $sourceFile), '/');

                    File::ensureDirectoryExists(dirname($sourceReplacement));
                    File::copy($sourceFile, $sourceReplacement);

                    $replacement = Str::after($sourceReplacement, $importmap->rootPath.'/public/');

                    $this->output->writeln(sprintf(
                        '    copied %s to %s',
                        $file,
                        $replacement,
                    ));

                    return $replacement;
                });

            $this->info('Generating cached manifest...');

            $preloadModulePaths = $importmap->preloadedModulePaths(fn ($file) => $file);

            $optmizedJson = collect($imports['imports'])
                ->map(function (string $oldFilename, string $module) use ($preloadModulePaths, $optimizedImports) {
                    return [
                        'module' => $module,
                        'path' => $optimizedImports[$module] ?? $oldFilename,
                        'preload' => in_array($oldFilename, $preloadModulePaths),
                    ];
                })
                ->values()
                ->all();

            File::put(Manifest::path(), json_encode($optmizedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $this->info('Done!');

        return self::SUCCESS;
    }

    private function digest(string $filename, string $fileSource): string
    {
        return preg_replace(
            '#(\.jsm?)$#',
            sprintf('-%s$1', (new FileDigest())($fileSource)),
            $filename
        );
    }
}
