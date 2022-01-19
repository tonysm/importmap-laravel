<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Tonysm\ImportmapLaravel\Importmap;

class JsonCommand extends Command
{
    public $signature = 'importmap:json';

    public $description = 'Displays the generated importmaps JSON based on your current pins.';

    public function handle(Importmap $importmap): int
    {
        $imports = $importmap->asArray('asset');

        $this->output->writeln(json_encode($imports, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
