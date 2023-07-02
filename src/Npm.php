<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class Npm
{
    private string $baseUrl = 'https://registry.npmjs.org';

    public function __construct(private ?string $configPath = null)
    {
        $this->configPath ??= base_path('routes/importmap.php');
    }

    public function outdatedPackages(): Collection
    {
        return $this->packagesWithVersion()
            ->reduce(function (Collection $outdatedPackages, PackageVersion $package) {
                $latestVersion = null;
                $error = null;

                if (! ($response = $this->getPackage($package))) {
                    $error = 'Response error';
                } elseif ($response['error'] ?? false) {
                    $error = $response['error'];
                } else {
                    $latestVersion = $this->findLatestVersion($response);

                    if (! $this->outdated($package->version, $latestVersion)) {
                        return $outdatedPackages;
                    }
                }

                return $outdatedPackages->add(new OutdatedPackage(
                    name: $package->name,
                    currentVersion: $package->version,
                    latestVersion: $latestVersion,
                    error: $error,
                ));
            }, collect());
    }

    public function vulnerablePackages(): Collection
    {
        $data = $this->packagesWithVersion()
            ->mapWithKeys(fn (PackageVersion $package) => [
                $package->name => [$package->version],
            ])
            ->all();

        return $this->getAudit($data)
            ->collect()
            ->flatMap(function (array $vulnerabilities, string $package) {
                return collect($vulnerabilities)
                    ->map(fn (array $vulnerability) => new VulnerablePackage(
                        name: $package,
                        severity: $vulnerability['severity'],
                        vulnerableVersions: $vulnerability['vulnerable_versions'],
                        vulnerability: $vulnerability['title'],
                    ));
            })
            ->sortBy([
                ['name', 'asc'],
                ['severity', 'asc'],
            ])
            ->values();
    }

    public function packagesWithVersion(): Collection
    {
        $content = File::get($this->configPath);

        return $this->findPackagesFromCdnMatches($content)
            ->merge($this->findPackagesFromLocalMatches($content))
            ->unique('name')
            ->values();
    }

    private function findPackagesFromCdnMatches(string $content)
    {
        preg_match_all('/^Importmap\:\:pin\(.*(?<=npm:|npm\/|skypack\.dev\/|unpkg\.com\/)(.*)(?=@\d+\.\d+\.\d+)@(\d+\.\d+\.\d+(?:[^\/\s"]*)).*\)\;\r?$/m', $content, $matches);

        if (count($matches) !== 3) {
            return collect();
        }

        return collect($matches[1])
            ->zip($matches[2])
            ->map(fn ($items) => new PackageVersion(name: $items[0], version: $items[1]))
            ->values();
    }

    private function findPackagesFromLocalMatches(string $content)
    {
        preg_match_all('/^Importmap::pin\("([^"]*)".*\)\; \/\/.*@(\d+\.\d+\.\d+(?:[^\s]*)).*\r?$/m', $content, $matches);

        if (count($matches) !== 3) {
            return collect();
        }

        return collect($matches[1])
            ->zip($matches[2])
            ->map(fn ($items) => new PackageVersion(name: $items[0], version: $items[1]))
            ->values();
    }

    private function getPackage(PackageVersion $package)
    {
        $response = Http::get($this->baseUrl.'/'.$package->name);

        if (! $response->ok()) {
            return null;
        }

        return $response->json();
    }

    private function findLatestVersion(array $json)
    {
        $latestVersion = data_get($json, 'dist-tags.latest');

        if ($latestVersion) {
            return $latestVersion;
        }

        if (! isset($json['versions'])) {
            return;
        }

        return collect($json['versions'])
            ->keys()
            ->sort(fn ($versionA, $versionB) => version_compare($versionB, $versionA))
            ->values()
            ->first();
    }

    private function outdated(string $currentVersion, string $latestVersion)
    {
        return version_compare($currentVersion, $latestVersion) === -1;
    }

    private function getAudit(array $packages)
    {
        $response = Http::asJson()
            ->post($this->baseUrl.'/-/npm/v1/security/advisories/bulk', $packages);

        if (! $response->ok()) {
            return collect();
        }

        return $response->collect();
    }
}
