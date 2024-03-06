<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\PhpExecutableFinder;
use Tonysm\ImportmapLaravel\Actions\FixJsImportPaths;
use Tonysm\ImportmapLaravel\Actions\ReplaceOrAppendTags;

#[AsCommand('importmap:install')]
class InstallCommand extends Command
{
    public $signature = 'importmap:install';

    public $description = 'Installs the package.';

    public function handle(): int
    {
        File::ensureDirectoryExists(resource_path('js'));

        $this->convertLocalImportsFromUsingDots();
        $this->publishImportmapFiles();
        $this->importDependenciesFromNpm();
        $this->updateAppLayouts();
        $this->deleteNpmRelatedFiles();
        $this->configureIgnoredFolder();
        $this->runStorageLinkCommand();

        $this->newLine();
        $this->components->info('Importmap Laravel was installed succesfully.');

        return self::SUCCESS;
    }

    private function deleteNpmRelatedFiles(): void
    {
        $files = [
            'package.json',
            'package-lock.json',
            'webpack.mix.js',
            'postcss.config.js',
            'vite.config.js',
        ];

        collect($files)
            ->map(fn ($file) => base_path($file))
            ->filter(fn ($file) => File::exists($file))
            ->each(fn ($file) => File::delete($file));
    }

    private function publishImportmapFiles(): void
    {
        File::copy(dirname(__DIR__, 2).implode(DIRECTORY_SEPARATOR, ['', 'stubs', 'routes', 'importmap.php']), base_path(implode(DIRECTORY_SEPARATOR, ['routes', 'importmap.php'])));
        File::copy(dirname(__DIR__, 2).implode(DIRECTORY_SEPARATOR, ['', 'stubs', 'jsconfig.json']), base_path('jsconfig.json'));
    }

    private function convertLocalImportsFromUsingDots(): void
    {
        (new FixJsImportPaths(rtrim(resource_path('js'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))();
    }

    private function importDependenciesFromNpm(): void
    {
        if (! File::exists($packageJsonFile = base_path('package.json'))) {
            return;
        }

        $filteredOutDependencies = [
            '@tailwindcss/forms',
            '@tailwindcss/typography',
            'autoprefixer',
            'laravel-vite-plugin',
            'postcss',
            'tailwindcss',
            'vite',
        ];

        $packageJson = json_decode(File::get($packageJsonFile), true);

        $dependencies = collect(array_replace($packageJson['dependencies'] ?? [], $packageJson['devDependencies'] ?? []))
            ->filter(fn ($_version, $package) => ! in_array($package, $filteredOutDependencies))
            // Axios has an issue with importmaps, so we'll hardcode the version for now...
            ->map(fn ($version, $package) => $package === 'axios' ? 'axios@0.27' : "\"{$package}@{$version}\"");

        if (trim($dependencies->join('')) === '') {
            return;
        }

        Process::forever()->run(array_merge([
            $this->phpBinary(),
            'artisan',
            'importmap:pin',
        ], $dependencies->all()), function ($_type, $output) {
            $this->output->write($output);
        });
    }

    private function updateAppLayouts(): void
    {
        $this->existingLayoutFiles()->each(fn ($file) => File::put(
            $file,
            (new ReplaceOrAppendTags())(File::get($file)),
        ));
    }

    private function existingLayoutFiles()
    {
        return collect(['app', 'guest'])
            ->map(fn ($file) => resource_path("views/layouts/{$file}.blade.php"))
            ->filter(fn ($file) => File::exists($file));
    }

    private function configureIgnoredFolder()
    {
        if (Str::contains(File::get(base_path('.gitignore')), 'public/js')) {
            return;
        }

        File::append(base_path('.gitignore'), "\n/public/js\n");
    }

    private function runStorageLinkCommand()
    {
        if ($this->components->confirm('To be able to serve your assets in development, the resource/js folder will be symlinked to your public/js. Would you like to do that now?', true)) {
            if ($this->usingSail() && ! env('LARAVEL_SAIL')) {
                Process::forever()->run([
                    './vendor/bin/sail',
                    'up',
                    '-d',
                ], function ($_type, $output) {
                    $this->output->write($output);
                });

                Process::forever()->run([
                    './vendor/bin/sail',
                    'artisan',
                    'storage:link',
                ], function ($_type, $output) {
                    $this->output->write($output);
                });
            } else {
                Process::forever()->run([
                    $this->phpBinary(),
                    'artisan',
                    'storage:link',
                ], function ($_type, $output) {
                    $this->output->write($output);
                });
            }
        }
    }

    private function usingSail(): bool
    {
        return file_exists(base_path('docker-compose.yml')) && str_contains(file_get_contents(base_path('composer.json')), 'laravel/sail');
    }

    private function phpBinary()
    {
        return (new PhpExecutableFinder())->find(false) ?: 'php';
    }
}
