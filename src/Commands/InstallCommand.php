<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Terminal;
use Tonysm\ImportmapLaravel\Actions\FixJsImportPaths;
use Tonysm\ImportmapLaravel\Actions\ReplaceOrAppendTags;
use Tonysm\ImportmapLaravel\Events\FailedToFixImportStatement;

#[AsCommand('importmap:install')]
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
        $this->publishImportmapFiles();
        $this->importDependenciesFromNpm();
        $this->updateAppLayouts();
        $this->deleteNpmRelatedFiles();
        $this->configureIgnoredFolder();

        $this->displayAfterNotes();

        $this->newLine();
        $this->line(' <fg=white>Done!</>');

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

    private function publishImportmapFiles(): void
    {
        $this->displayTask('publishing the `routes/importmap.php` file', function () {
            File::copy(dirname(__DIR__, 2).implode(DIRECTORY_SEPARATOR, ['', 'stubs', 'routes', 'importmap.php']), base_path(implode(DIRECTORY_SEPARATOR, ['routes', 'importmap.php'])));
            File::copy(dirname(__DIR__, 2).implode(DIRECTORY_SEPARATOR, ['', 'stubs', 'jsconfig.json']), base_path('jsconfig.json'));

            return self::SUCCESS;
        });
    }

    private function convertLocalImportsFromUsingDots(): void
    {
        Event::listen(function (FailedToFixImportStatement $event) {
            $this->afterMessages[] = sprintf(
                'Failed to fix import statement (%s) in file (%).',
                $event->importStatement,
                str_replace(base_path(), '', $event->file->getPath()),
            );
        });

        $this->displayTask('converting js imports', function () {
            $root = rtrim(resource_path('js'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

            (new FixJsImportPaths($root))();

            return self::SUCCESS;
        });
    }

    private function importDependenciesFromNpm(): void
    {
        $this->displayTask('pinning dependencies from NPM', function () {
            if (! File::exists($packageJsonFile = base_path('package.json'))) {
                $this->afterMessages[] = '<fg=white>* Pinning was skipped because of missing package.json</>';

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
                // Axios has an issue with importmaps, so we'll hardcode the version for now...
                ->map(fn ($version, $package) => $package === 'axios' ? 'axios@0.27' : "\"{$package}@{$version}\"")
                ->join(' ');

            if (trim($dependencies) === '') {
                return self::SUCCESS;
            }

            return Artisan::call("importmap:pin {$dependencies}");
        });
    }

    private function updateAppLayouts(): void
    {
        $this->displayTask('Updating layout files', function () {
            $this->existingLayoutFiles()
                ->each(fn ($file) => File::put(
                    $file,
                    (new ReplaceOrAppendTags())(File::get($file)),
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
        if (Str::contains(File::get(base_path('.gitignore')), 'public/js')) {
            return;
        }

        $this->displayTask('dumping & ignoring `public/js` folder', function () {
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
