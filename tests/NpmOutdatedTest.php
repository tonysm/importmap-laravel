<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->npm = new Npm(configPath: __DIR__ . "/fixtures/npm/outdated-importmap.php");

    Http::preventStrayRequests();
});

it("finds no outdated packages", function () {
    Http::fakeSequence()
        ->push(["dist-tags" => ["latest" => "3.0.0"]])
        ->push(["dist-tags" => ["latest" => "4.0.0"]]);

    expect($this->npm->outdatedPackages()->count())->toEqual(0);
});

it("handles error when fails to fetch latest version of package", function () {
    Http::fake([
        'https://registry.npmjs.org/is-svg' => Http::response([], 404),
        'https://registry.npmjs.org/lodash' => Http::response(["dist-tags" => ["latest" => "4.0.0"]]),
    ]);

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("is-svg");
    expect($packages->first()->currentVersion)->toEqual("3.0.0");
    expect($packages->first()->latestVersion)->toBeNull();
    expect($packages->first()->error)->toEqual("Response error");
});

it("handles error when returns ok but response json contains error", function () {
    Http::fake([
        'https://registry.npmjs.org/is-svg' => Http::response(["error" => "Something went wrong"]),
        'https://registry.npmjs.org/lodash' => Http::response(["dist-tags" => ["latest" => "4.0.0"]]),
    ]);

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("is-svg");
    expect($packages->first()->currentVersion)->toEqual("3.0.0");
    expect($packages->first()->latestVersion)->toBeNull();
    expect($packages->first()->error)->toEqual("Something went wrong");
});

it("finds outdated packages", function () {
    Http::fake([
        'https://registry.npmjs.org/is-svg' => Http::response(["dist-tags" => ["latest" => "4.0.0"]]),
        'https://registry.npmjs.org/lodash' => Http::response([
            "versions" => [
                "2.0.0" => [],
                "5.0.0" => [],
                "1.2.0" => [],
                "1.7.0" => [],
            ],
        ]),
    ]);

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(2);

    $svgPackage = $packages->firstWhere('name', 'is-svg');

    expect($svgPackage->name)->toEqual("is-svg");
    expect($svgPackage->currentVersion)->toEqual("3.0.0");
    expect($svgPackage->latestVersion)->toEqual("4.0.0");
    expect($svgPackage->error)->toBeNull();

    $lodashPackage = $packages->firstWhere('name', 'lodash');

    expect($lodashPackage->name)->toEqual("lodash");
    expect($lodashPackage->currentVersion)->toEqual("4.0.0");
    expect($lodashPackage->latestVersion)->toEqual("5.0.0");
    expect($lodashPackage->error)->toBeNull();
});
