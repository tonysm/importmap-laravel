<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Packager
{
    public const DEFAULT_CDN = 'jspm';

    public static $ENDPOINT = 'https://api.jspm.io/generate';

    public function __construct(
        public string $importmapPath = 'routes/importmap.php',
        public string $vendorPath = 'resources/js/vendor',
    ) {
        $this->importmapPath = file_exists(base_path($importmapPath))
            ? base_path($this->importmapPath)
            : $this->importmapPath;
    }

    public function import(array $packages, string $env, string $from)
    {
        $response = Http::post(static::$ENDPOINT, [
            'install' => $packages,
            'flattenScope' => true,
            'env' => ['browser', 'module', $env],
            'provider' => $from,
        ]);

        return match ($response->status()) {
            200 => $response->collect('map.imports'),
            404, 401 => null,
            default => $this->handleFailureResponse($response),
        };
    }

    public function pinFor(string $package, string $url): string
    {
        return sprintf('Importmap::pin("%s", to: "%s");', $package, $url);
    }

    public function download(string $package, string $url): void
    {
        File::ensureDirectoryExists(base_path($this->vendorPath));
        File::delete(base_path($this->vendoredPackageName($package)));
        File::put(base_path($this->vendoredPackageName($package)), $this->withoutSourceMapComments(Http::get($url)->body()));
    }

    public function vendoredPinFor(string $package, string $url): string
    {
        $version = $this->extractPackageVersionFrom($url);

        return sprintf(
            'Importmap::pin("%s", to: "%s"); // %s',
            $package,
            Str::after($this->vendoredPackageName($package), 'resources'),
            $version,
        );
    }

    public function packaged(string $package): bool
    {
        return (bool) preg_match(
            sprintf('#Importmap::pin\(["\']%s["\']#', preg_quote($package)),
            File::get($this->importmapPath),
        );
    }

    public function remove(string $package): void
    {
        $this->removeExistingPackageFile($package);
        $this->removePackageFromImportmap($package);
    }

    private function removeExistingPackageFile(string $package): void
    {
        if (File::exists(base_path($this->vendoredPackageName($package)))) {
            File::delete(base_path($this->vendoredPackageName($package)));
        }

        if (File::exists(base_path($this->vendorPath)) && count(File::files(base_path($this->vendorPath))) === 0) {
            File::deleteDirectory(base_path($this->vendorPath));
        }
    }

    private function removePackageFromImportmap(string $package)
    {
        $contents = collect(File::lines($this->importmapPath))
            ->reject(fn (string $line) => (
                preg_match(sprintf('#Importmap::pin\(["\']%s["\']#', preg_quote($package)), $line)
            ))
            ->join(PHP_EOL);

        File::put($this->importmapPath, $contents);
    }

    private function withoutSourceMapComments(string $contents): string
    {
        return preg_replace('#//\# sourceMappingURL=.*#', '', $contents);
    }

    private function vendoredPackageName(string $package): string
    {
        return sprintf('%s/%s', rtrim($this->vendorPath, '/'), $this->packageFilename($package));
    }

    private function packageFilename(string $package): string
    {
        return str_replace('/', '--', $package).'.js';
    }

    private function extractPackageVersionFrom(string $url): string
    {
        preg_match('#(@\d+\.\d+\.\d+)/#', $url, $matches);

        if (! ($matches[1] ?? false)) {
            return 'Unknown Version';
        }

        return $matches[1];
    }

    private function handleFailureResponse(Response $response)
    {
        if ($errorMessage = $response->json('error', null)) {
            throw Exceptions\ImportmapException::withResponseError($errorMessage);
        }

        throw Exceptions\ImportmapException::withUnexpectedResponseCode($response->status());
    }
}
