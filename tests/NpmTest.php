<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\Http;
use Tonysm\ImportmapLaravel\Tests\TestCase;

class NpmTest extends TestCase
{
    private Npm $npm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importmap = new Importmap();
        $this->npm = new Npm($this->importmap);
    }

    /** @test */
    public function no_oudated_packages()
    {
        $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

        Http::fake(fn () => Http::response([
            "dist-tags" => [
                "latest" => "2.2.0",
            ],
        ]));

        $this->assertCount(0, $this->npm->outdatedPackages());
    }

    /** @test */
    public function handles_error_when_fails_to_fetch_latest_version_of_package()
    {
        $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

        Http::fake(fn () => Http::response([], 404));

        $this->assertCount(1, $packages = $this->npm->outdatedPackages());
        $this->assertEquals("md5", $packages->first()->name);
        $this->assertEquals("2.2.0", $packages->first()->currentVersion);
        $this->assertNull($packages->first()->latestVersion);
        $this->assertEquals("Response error", $packages->first()->error);
    }

    /** @test */
    public function handles_error_when_returns_ok_but_response_json_contains_error()
    {
        $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

        Http::fake(fn () => Http::response([
            "error" => "Something went wrong",
        ], 200));

        $this->assertCount(1, $packages = $this->npm->outdatedPackages());
        $this->assertEquals("md5", $packages->first()->name);
        $this->assertEquals("2.2.0", $packages->first()->currentVersion);
        $this->assertNull($packages->first()->latestVersion);
        $this->assertEquals("Something went wrong", $packages->first()->error);
    }

    /** @test */
    public function finds_outdated_package()
    {
        $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

        Http::fake(fn () => Http::response([
            "dist-tags" => [
                "latest" => "2.2.1",
            ],
        ]));

        $this->assertCount(1, $packages = $this->npm->outdatedPackages());
        $this->assertEquals("md5", $packages->first()->name);
        $this->assertEquals("2.2.0", $packages->first()->currentVersion);
        $this->assertEquals("2.2.1", $packages->first()->latestVersion);
        $this->assertNull($packages->first()->error);
    }

    /** @test */
    public function finds_outdated_package_comparing_versions()
    {
        $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

        Http::fake(fn () => Http::response([
            "versions" => [
                "2.0.0" => [],
                "2.2.2" => [],
                "1.2.0" => [],
                "1.7.0" => [],
            ],
        ]));

        $this->assertCount(1, $packages = $this->npm->outdatedPackages());
        $this->assertEquals("md5", $packages->first()->name);
        $this->assertEquals("2.2.0", $packages->first()->currentVersion);
        $this->assertEquals("2.2.2", $packages->first()->latestVersion);
        $this->assertNull($packages->first()->error);
    }

    /** @test */
    public function finds_no_audit_vulnerabilities()
    {
        $this->importmap->pin("is-svg", "https://cdn.skypack.dev/is-svg@3.0.0");

        Http::fake(fn () => Http::response([
            "is-svg" => [
                [
                    "title" => "Regular Expression Denial of Service (ReDoS)",
                    "severity" => "high",
                    "vulnerable_versions" => ">=2.1.0 <4.2.2",
                ],
                [
                    "title" => "ReDOS in IS-SVG",
                    "severity" => "high",
                    "vulnerable_versions" => ">=2.1.0 <4.3.0",
                ],
            ],
        ]));

        $this->assertCount(2, $vulnerabilities = $this->npm->vulnerablePackages());

        $this->assertEquals("is-svg", $vulnerabilities->first()->name);
        $this->assertEquals("Regular Expression Denial of Service (ReDoS)", $vulnerabilities->first()->vulnerability);
        $this->assertEquals("high", $vulnerabilities->first()->severity);
        $this->assertEquals(">=2.1.0 <4.2.2", $vulnerabilities->first()->vulnerableVersions);

        $this->assertEquals("is-svg", $vulnerabilities->last()->name);
        $this->assertEquals("ReDOS in IS-SVG", $vulnerabilities->last()->vulnerability);
        $this->assertEquals("high", $vulnerabilities->last()->severity);
        $this->assertEquals(">=2.1.0 <4.3.0", $vulnerabilities->last()->vulnerableVersions);
    }
}
