<?php

namespace Tonysm\ImportmapLaravel;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->npm = new Npm(configPath: __DIR__ . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, ["fixtures", "npm", "audit-importmap.php"]));

    Http::preventStrayRequests();
});

it("finds no audit vulnerabilities", function () {
    Http::fake(fn () => Http::response([]));

    expect($this->npm->vulnerablePackages())->toHaveCount(0);

    Http::assertSent(fn (Request $request) => (
        $request->data() == [
            "is-svg" => ["3.0.0"],
            "lodash" => ["4.17.12"],
        ]
    ));
});

it("finds audit vulnerabilities", function () {
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

    Http::assertSent(fn (Request $request) => (
        $request->data() == [
            "is-svg" => ["3.0.0"],
            "lodash" => ["4.17.12"],
        ]
    ));
});
