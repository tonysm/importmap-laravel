<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->npm = new Npm(configPath: __DIR__ . "/fixtures/npm/outdated-importmap.php");
});

it("finds no outdated packages", function () {
    Http::fakeSequence()
        ->push(["dist-tags" => ["latest" => "3.0.0"]])
        ->push(["dist-tags" => ["latest" => "4.0.0"]]);

    expect($this->npm->outdatedPackages()->count())->toEqual(0);
});

it("handles error when fails to fetch latest version of package", function () {
    Http::fakeSequence()
        ->push([], 404)
        ->push(["dist-tags" => ["latest" => "4.0.0"]]);

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("is-svg");
    expect($packages->first()->currentVersion)->toEqual("3.0.0");
    expect($packages->first()->latestVersion)->toBeNull();
    expect($packages->first()->error)->toEqual("Response error");
});

it("handles error when returns ok but response json contains error", function () {
    Http::fakeSequence()
        ->push(["error" => "Something went wrong"])
        ->push(["dist-tags" => ["latest" => "4.0.0"]]);

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(1);
    expect($packages->first()->name)->toEqual("is-svg");
    expect($packages->first()->currentVersion)->toEqual("3.0.0");
    expect($packages->first()->latestVersion)->toBeNull();
    expect($packages->first()->error)->toEqual("Something went wrong");
});

it("finds outdated packages", function () {
    Http::fakeSequence()
        ->push(["dist-tags" => ["latest" => "4.0.0"]])
        ->push([
            "versions" => [
                "2.0.0" => [],
                "5.0.0" => [],
                "1.2.0" => [],
                "1.7.0" => [],
            ],
        ]);

    expect($packages = $this->npm->outdatedPackages())->toHaveCount(2);

    expect($packages->first()->name)->toEqual("is-svg");
    expect($packages->first()->currentVersion)->toEqual("3.0.0");
    expect($packages->first()->latestVersion)->toEqual("4.0.0");
    expect($packages->first()->error)->toBeNull();

    expect($packages->last()->name)->toEqual("lodash");
    expect($packages->last()->currentVersion)->toEqual("4.0.0");
    expect($packages->last()->latestVersion)->toEqual("5.0.0");
    expect($packages->last()->error)->toBeNull();
});
