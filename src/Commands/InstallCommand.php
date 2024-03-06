<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
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
            ->map(fn ($version, $package) => $package === 'axios' ? 'axios@0.27' : "\"{$package}@{$version}\"")
            ->join(' ');

        if (trim($dependencies) === '') {
            return;
        }

        Process::forever()->run(array_merge([
            $this->phpBinary(),
            'artisan',
            'importmap:pin',
        ], $dependencies), function ($_type, $output) {
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
}
