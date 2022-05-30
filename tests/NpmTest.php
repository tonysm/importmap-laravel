<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->importmap = new Importmap();
    $this->npm = new Npm($this->importmap);
});

it("finds no outdated packages", function () {
    $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

    Http::fake(fn () => Http::response([
        "dist-tags" => [
            "latest" => "2.2.0",
        ],
    ]));

    expect($this->npm->outdatedPackages()->count())->toEqual(0);
});

it("handles error when fails to fetch latest version of package", function () {
    $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

    Http::fake(fn () => Http::response([], 404));

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("md5");
    expect($packages->first()->currentVersion)->toEqual("2.2.0");
    expect($packages->first()->latestVersion)->toBeNull();
    expect($packages->first()->error)->toEqual("Response error");
});

it("handles error when returns ok but response json contains error", function () {
    $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

    Http::fake(fn () => Http::response([
        "error" => "Something went wrong",
    ], 200));

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("md5");
    expect($packages->first()->currentVersion)->toEqual("2.2.0");
    expect($packages->first()->latestVersion)->toBeNull();
    expect($packages->first()->error)->toEqual("Something went wrong");
});

it("finds outdated packages", function () {
    $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

    Http::fake(fn () => Http::response([
        "dist-tags" => [
            "latest" => "2.2.1",
        ],
    ]));

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("md5");
    expect($packages->first()->currentVersion)->toEqual("2.2.0");
    expect($packages->first()->latestVersion)->toEqual("2.2.1");
    expect($packages->first()->error)->toBeNull();
});

it("finds outdated packages comparing versions", function () {
    $this->importmap->pin("md5", "https://cdn.skypack.dev/md5@2.2.0");

    Http::fake(fn () => Http::response([
        "versions" => [
            "2.0.0" => [],
            "2.2.2" => [],
            "1.2.0" => [],
            "1.7.0" => [],
        ],
    ]));

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("md5");
    expect($packages->first()->currentVersion)->toEqual("2.2.0");
    expect($packages->first()->latestVersion)->toEqual("2.2.2");
    expect($packages->first()->error)->toBeNull();
});

it("finds no audit vulnerabilities", function () {
    $this->importmap->pin("is-svg", "https://cdn.skypack.dev/is-svg@3.0.0");

    Http::fake(fn () => Http::response([]));

    expect($this->npm->vulnerablePackages())->toHaveCount(0);
});

it("finds audit vulnerabilities", function () {
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

    expect($vulnerabilities = $this->npm->vulnerablePackages())->toHaveCount(2);

    expect($vulnerabilities->first()->name)->toEqual("is-svg");
    expect($vulnerabilities->first()->vulnerability)->toEqual("Regular Expression Denial of Service (ReDoS)");
    expect($vulnerabilities->first()->severity)->toEqual("high");
    expect($vulnerabilities->first()->vulnerableVersions)->toEqual(">=2.1.0 <4.2.2");

    expect($vulnerabilities->last()->name)->toEqual("is-svg");
    expect($vulnerabilities->last()->vulnerability)->toEqual("ReDOS in IS-SVG");
    expect($vulnerabilities->last()->severity)->toEqual("high");
    expect($vulnerabilities->last()->vulnerableVersions)->toEqual(">=2.1.0 <4.3.0");
});
