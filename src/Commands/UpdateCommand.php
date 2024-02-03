<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Tonysm\ImportmapLaravel\Npm;

#[AsCommand('importmap:update')]
class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importmap:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update outdated pinned packages.';

    public function handle(Npm $npm)
    {
        $this->call('importmap:pin', [
            'packages' => $npm->outdatedPackages()->pluck('name')->all(),
        ]);
    }
}
