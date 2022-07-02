<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use SplFileInfo;
use Symfony\Component\Console\Terminal;

class InstallCommand extends Command
{
    public $signature = 'importmap:install';

    public $description = 'Installs the package.';

    public $afterMessages = [];

    public function handle(): int
    {
        $this->displayHeader('Installing Importmap Laravel', '<bg=blue;fg=black> INFO </>');

        File::ensureDirectoryExists(resource_path('js'));

        $this->convertLocalImportsFromUsingDots();
        $this->publishImportmapFile();
        $this->importDependenciesFromNpm();
        $this->updateAppLayouts();
        $this->deleteNpmRelatedFiles();
        $this->configureJsSymlink();
        $this->configureIgnoredFolder();

        $this->displayAfterNotes();

        $this->newLine();
        $this->line(" <fg=white>Done!</>");

        return self::SUCCESS;
    }

    private function deleteNpmRelatedFiles(): void
    {
        $this->displayTask('removing NPM related files', function () {
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

            return self::SUCCESS;
        });
    }

    private function publishImportmapFile(): void
    {
        $this->displayTask('publishing the `routes/importmap.php` file', function () {
            File::copy(__DIR__ . '/../../stubs/routes/importmap.php', base_path('routes/importmap.php'));

            return self::SUCCESS;
        });
    }

    private function convertLocalImportsFromUsingDots(): void
    {
        $this->displayTask('converting js imports', function () {
            collect(File::allFiles(resource_path('js')))
                ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), ['js', 'mjs']))
                ->each(fn (SplFileInfo $file) => File::put(
                    $file->getRealPath(),
                    preg_replace(
                        "/import (.*['\"])\.\/(.*)/",
                        "import $1$2",
                        File::get($file->getRealPath()),
                    ),
                ));

            return self::SUCCESS;
        });
    }

    private function importDependenciesFromNpm(): void
    {
        $this->displayTask('pinning dependencies from NPM', function () {
            if (! File::exists($packageJsonFile = base_path('package.json'))) {
                $this->afterMessages[] = "<fg=white>* Pinning was skipped because of missing package.json</>";

                return self::INVALID;
            }

            $this->afterMessages[] = '<fg=white>* Some dev dependencies could\'ve been skipped...</>';

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
                // Axios had an issue with importmaps at the version currently required by Laravel, so we'll try the latest one instead...
                ->map(fn ($version, $package) => $package === 'axios' ? 'axios' : "\"{$package}@{$version}\"")
                ->join(' ');

            return Artisan::call("importmap:pin {$dependencies}");
        });
    }

    private function updateAppLayouts(): void
    {
        if (File::exists(base_path('webpack.mix.js'))) {
            $this->updateAppLayoutsUsingMix();
        } elseif (File::exists(base_path('vite.config.js'))) {
            $this->updateAppLayoutsUsingVite();
        } else {
            $this->appendImportmapTagsToLayoutsHead();
        }
    }

    private function updateAppLayoutsUsingMix()
    {
        $this->displayTask('replacing Mix functions in layouts', function () {
            $this->existingLayoutFiles()
                ->each(fn ($file) => File::put(
                    $file,
                    str_replace(
                        "<script src=\"{{ mix('js/app.js') }}\" defer></script>",
                        '<x-importmap-tags />',
                        File::get($file),
                    ),
                ));

            return self::SUCCESS;
        });
    }

    private function updateAppLayoutsUsingVite()
    {
        $this->displayTask('replacing Vite functions in layouts', function () {
            $this->existingLayoutFiles()
                ->each(fn ($file) => File::put(
                    $file,
                    preg_replace(
                        '/(\s*)(\@vite\(\[.*)\'resources\/js\/app.js\'(.*\]\))/',
                        "\\1\\2\\3\n\\1<x-importmap-tags />",
                        File::get($file),
                    ),
                ))
                ->each(fn ($file) => File::put(
                    $file,
                    preg_replace(
                        '/.*\@vite\(\[\]\).*\n/',
                        '',
                        File::get($file),
                    ),
                ));

            return self::SUCCESS;
        });
    }

    private function appendImportmapTagsToLayoutsHead(): void
    {
        $this->displayTask('adding importmap tags to layouts', function () {
            $this->existingLayoutFiles()
                ->each(fn ($file) => File::put(
                    $file,
                    preg_replace(
                        '/(\s*)(<\/head>)/',
                        "\\1    <x-importmap-tags />\n\\1\\2",
                        File::get($file),
                    ),
                ));

            return self::SUCCESS;
        });
    }

    private function existingLayoutFiles()
    {
        return collect(['app', 'guest'])
            ->map(fn ($file) => resource_path("views/layouts/{$file}.blade.php"))
            ->filter(fn ($file) => File::exists($file));
    }

    private function configureJsSymlink()
    {
        $this->displayTask('configuring JS symlink', function () {
            File::put(
                $configFile = base_path('config/filesystems.php'),
                preg_replace(
                    '/(\s*)\'links\' => \[((?:.|\n)*)(?:],)+?/',
                    "\\1'links' => array_filter([\n\\1    public_path('js') => env('APP_ENV') === 'local' ? resource_path('js') : null,\\2]),",
                    File::get($configFile),
                ),
            );

            File::put(
                $configFile,
                preg_replace(
                    '/(array_filter\(\[)\n\n/',
                    '\\1',
                    File::get($configFile),
                ),
            );

            $this->afterMessages[] = "<fg=white>* To create the symlink, run:</>\n\n\n    <fg=yellow>    php artisan storage:link</>\n";

            return self::SUCCESS;
        });
    }

    private function displayHeader($text, $prefix)
    {
        $this->newLine();
        $this->line(sprintf(' %s <fg=white>%s</>  ', $prefix, $text));
        $this->newLine();
    }

    private function displayTask($description, $task)
    {
        $width = (new Terminal())->getWidth();
        $dots = max(str_repeat('<fg=gray>.</>', $width - strlen($description) - 13), 0);
        $this->output->write(sprintf('    <fg=white>%s</> %s ', $description, $dots));
        $output = $task();

        if ($output === self::SUCCESS) {
            $this->output->write('<info>DONE</info>');
        } elseif ($output === self::FAILURE) {
            $this->output->write('<error>FAIL</error>');
        } elseif ($output === self::INVALID) {
            $this->output->write('<fg=yellow>WARN</>');
        }

        $this->newLine();
    }

    private function configureIgnoredFolder()
    {
        $this->displayTask('dumping & ignoring `public/js` folder', function () {
            if (File::isDirectory($publicJsFolder = public_path('js'))) {
                File::cleanDirectory($publicJsFolder);
                File::deleteDirectory($publicJsFolder);
            }

            File::append(
                base_path('.gitignore'),
                "\n/public/js\n"
            );

            return self::SUCCESS;
        });
    }

    private function displayAfterNotes()
    {
        if (count($this->afterMessages) > 0) {
            $this->displayHeader('After Notes & Next Steps', '<bg=yellow;fg=black> NOTES </>');

            foreach ($this->afterMessages as $message) {
                $this->line('    '.$message);
            }
        }
    }
}
