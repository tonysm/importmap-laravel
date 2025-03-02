<?php

namespace Tonysm\ImportmapLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Tonysm\ImportmapLaravel\Npm;
use Tonysm\ImportmapLaravel\VulnerablePackage;

#[AsCommand('importmap:audit')]
class AuditCommand extends Command
{
    public $signature = 'importmap:audit';

    public $description = 'Run a security audit.';

    public function handle(Npm $npm): int
    {
        $vulnerablePackages = $npm->vulnerablePackages();

        if ($vulnerablePackages->isEmpty()) {
            $this->info('No vulnerable packages found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Package', 'Severity', 'Vulnerable Versions', 'Vulnerability'],
            $vulnerablePackages
                ->map(fn (VulnerablePackage $package): array => [$package->name, $package->severity, $package->vulnerableVersions, $package->vulnerability])
                ->all()
        );

        $this->newLine();

        $summary = $vulnerablePackages
            ->groupBy('severity')
            ->map(fn ($vulns): int => $vulns->count())
            ->sortDesc()
            ->map(fn ($count, $severity): string => "$count {$severity}")
            ->join(', ');

        $this->error(sprintf(
            '%d %s found: %s',
            $vulnerablePackages->count(),
            Str::plural('vulnerability', $vulnerablePackages->count()),
            $summary,
        ));

        return self::FAILURE;
    }
}
