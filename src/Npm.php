<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Npm
{
    private string $baseUrl = "https://registry.npmjs.org";

    public function __construct(private Importmap $importmap)
    {
    }

    public function outdatedPackages(): Collection
    {
        return $this->packagesWithVersion()->reduce(function (Collection $outdatedPackages, string $url) {
            $package = $this->extractVendorName($url);

            if (! $package) {
                return $outdatedPackages;
            }

            if (! ($response = $this->getPackage($package))) {
                $package->error = "Response error";
            } elseif ($response["error"] ?? false) {
                $package->error = $response["error"];
            } else {
                $latestVersion = $this->findLatestVersion($response);

                if (! $this->outdated($package->currentVersion, $latestVersion)) {
                    return $outdatedPackages;
                }

                $package->latestVersion = $latestVersion;
            }

            return $outdatedPackages->add($package);
        }, collect());
    }

    public function vulnerablePackages(): Collection
    {
        $data = $this->packagesWithVersion()
            ->map(fn ($url) => $this->extractVendorName($url))
            ->mapWithKeys(fn (OutdatedPackage $package) => [
                $package->name => [$package->currentVersion],
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

    private function packagesWithVersion(): Collection
    {
        return collect($this->importmap->asArray(fn ($url) => $url)["imports"]);
    }

    private function extractVendorName(string $url)
    {
        $matches = null;
        preg_match('/^.*(?<=npm:|npm\/|skypack\.dev\/|unpkg\.com\/)(.*)(?=@\d+\.\d+\.\d+)@(\d+\.\d+\.\d+(?:[^\/\s"]*)).*$/', $url, $matches);

        if (count($matches) !== 3) {
            return null;
        }

        return new OutdatedPackage(name: $matches[1], currentVersion: $matches[2]);
    }

    private function getPackage(OutdatedPackage $package)
    {
        $response = Http::get($this->baseUrl . "/" . $package->name);

        if (! $response->ok()) {
            return null;
        }

        return $response->json();
    }

    private function findLatestVersion(array $json)
    {
        $latestVersion = data_get($json, "dist-tags.latest");

        if ($latestVersion) {
            return $latestVersion;
        }

        if (! isset($json["versions"])) {
            return;
        }

        return collect($json["versions"])
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
            ->post($this->baseUrl . "/-/npm/v1/security/advisories/bulk", $packages);

        if (! $response->ok()) {
            return collect();
        }

        return $response->collect();
    }
}
