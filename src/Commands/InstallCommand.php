<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    public $signature = 'importmap:install';

    public $description = 'Installs the package.';

    public function handle(): int
    {
        if (! $this->confirm('This command will purge your resources/js folder and replace with a new one. Do you want to continue?')) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $this->info('Purging the existing resources/js files...');
        File::cleanDirectory(resource_path('js'));
        File::ensureDirectoryExists(resource_path('js'));

        $this->info('Copying the scaffold files...');
        File::copy(__DIR__ . '/../../stubs/js/app.js', resource_path('js/app.js'));
        File::copy(__DIR__ . '/../../stubs/js/bootstrap.js', resource_path('js/bootstrap.js'));
        File::copy(__DIR__ . '/../../stubs/routes/importmap.php', base_path('routes/importmap.php'));

        $this->info('Done!');

        return self::SUCCESS;
    }
}
