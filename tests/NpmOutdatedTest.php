<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Support\Facades\Http;
use Tonysm\ImportmapLaravel\Npm;

class NpmOutdatedTest extends TestCase
{
    private Npm $npm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->npm = new Npm(configPath: __DIR__.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, ['fixtures', 'npm', 'outdated-importmap.php']));

        Http::preventStrayRequests();
    }

    /** @test */
    public function finds_no_outdated_packages()
    {
        Http::fakeSequence()
            ->push(['dist-tags' => ['latest' => '3.0.0']])
            ->push(['dist-tags' => ['latest' => '4.0.0']]);

        $this->assertCount(0, $this->npm->outdatedPackages());
    }

    /** @test */
    public function handles_error_when_fails_to_fetch_latest_version_of_package()
    {
        Http::fake([
            'https://registry.npmjs.org/is-svg' => Http::response([], 404),
            'https://registry.npmjs.org/lodash' => Http::response(['dist-tags' => ['latest' => '4.0.0']]),
        ]);

        $this->assertCount(1, $packages = $this->npm->outdatedPackages());

        $this->assertEquals('is-svg', $packages->first()->name);
        $this->assertEquals('3.0.0', $packages->first()->currentVersion);
        $this->assertNull($packages->first()->latestVersion);
        $this->assertEquals('Response error', $packages->first()->error);
    }

    /** @test */
    public function handles_error_when_returns_ok_but_response_json_contains_error()
    {
        Http::fake([
            'https://registry.npmjs.org/is-svg' => Http::response(['error' => 'Something went wrong']),
            'https://registry.npmjs.org/lodash' => Http::response(['dist-tags' => ['latest' => '4.0.0']]),
        ]);

        $this->assertCount(1, $packages = $this->npm->outdatedPackages());

        $this->assertEquals('is-svg', $packages->first()->name);
        $this->assertEquals('3.0.0', $packages->first()->currentVersion);
        $this->assertNull($packages->first()->latestVersion);
        $this->assertEquals('Something went wrong', $packages->first()->error);
    }

    /** @test */
    public function finds_outdated_packages()
    {
        Http::fake([
            'https://registry.npmjs.org/is-svg' => Http::response(['dist-tags' => ['latest' => '4.0.0']]),
            'https://registry.npmjs.org/lodash' => Http::response([
                'versions' => [
                    '2.0.0' => [],
                    '5.0.0' => [],
                    '1.2.0' => [],
                    '1.7.0' => [],
                ],
            ]),
        ]);

        $this->assertCount(2, $packages = $this->npm->outdatedPackages());

        $svgPackage = $packages->firstWhere('name', 'is-svg');

        $this->assertEquals('is-svg', $svgPackage->name);
        $this->assertEquals('3.0.0', $svgPackage->currentVersion);
        $this->assertEquals('4.0.0', $svgPackage->latestVersion);
        $this->assertNull($svgPackage->error);

        $lodashPackage = $packages->firstWhere('name', 'lodash');

        $this->assertEquals('lodash', $lodashPackage->name);
        $this->assertEquals('4.0.0', $lodashPackage->currentVersion);
        $this->assertEquals('5.0.0', $lodashPackage->latestVersion);
        $this->assertNull($lodashPackage->error);
    }
}
