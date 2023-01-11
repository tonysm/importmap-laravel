<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tonysm\ImportmapLaravel\Npm;

class NpmAuditTest extends TestCase
{
    private Npm $npm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->npm = new Npm(configPath: __DIR__ . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, ["fixtures", "npm", "audit-importmap.php"]));

        Http::preventStrayRequests();
    }

    /** @test */
    public function finds_no_audit_vulnerabilities()
    {
        Http::fake(fn () => Http::response([]));

        $this->assertCount(0, $this->npm->vulnerablePackages());

        Http::assertSent(fn (Request $request) => (
            $request->data() == [
                "is-svg" => ["3.0.0"],
                "lodash" => ["4.17.12"],
            ]
        ));
    }

    /** @test */
    public function finds_audit_vulnerabilities()
    {
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

        $this->assertEquals('is-svg', $vulnerabilities->first()->name);
        $this->assertEquals('Regular Expression Denial of Service (ReDoS)', $vulnerabilities->first()->vulnerability);
        $this->assertEquals('high', $vulnerabilities->first()->severity);
        $this->assertEquals('>=2.1.0 <4.2.2', $vulnerabilities->first()->vulnerableVersions);

        $this->assertEquals('is-svg', $vulnerabilities->last()->name);
        $this->assertEquals('ReDOS in IS-SVG', $vulnerabilities->last()->vulnerability);
        $this->assertEquals('high', $vulnerabilities->last()->severity);
        $this->assertEquals('>=2.1.0 <4.3.0', $vulnerabilities->last()->vulnerableVersions);

        Http::assertSent(fn (Request $request) => (
            $request->data() == [
                "is-svg" => ["3.0.0"],
                "lodash" => ["4.17.12"],
            ]
        ));
    }
}
