<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Tonysm\ImportmapLaravel\AssetResolver;
use Tonysm\ImportmapLaravel\Importmap;

#[AsCommand('importmap:json')]
class JsonCommand extends Command
{
    public $signature = 'importmap:json';

    public $description = 'Displays the generated importmaps JSON based on your current pins.';

    public function handle(Importmap $importmap): int
    {
        $imports = $importmap->asArray(new AssetResolver());

        $this->output->writeln(json_encode($imports, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
