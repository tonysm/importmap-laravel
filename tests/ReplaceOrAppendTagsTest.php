<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Tonysm\ImportmapLaravel\Actions\ReplaceOrAppendTags;

class ReplaceOrAppendTagsTest extends TestCase
{
    /** @test */
    public function replace_vite_tags()
    {
        $contents = <<<'BLADE'
        <!DOCTYPE html>
        <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="csrf-token" content="{{ csrf_token() }}">

                <title>{{ config('app.name', 'Laravel') }}</title>

                <!-- Fonts -->
                <link rel="preconnect" href="https://fonts.bunny.net">
                <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

                <!-- Scripts -->
                @vite(['resources/js/app.js', 'resources/css/app.css'])
            </head>
            <body class="font-sans antialiased">
                 <!-- ...  -->
            </body>
        </html>
        BLADE;

        $expected = <<<'BLADE'
        <!DOCTYPE html>
        <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="csrf-token" content="{{ csrf_token() }}">

                <title>{{ config('app.name', 'Laravel') }}</title>

                <!-- Fonts -->
                <link rel="preconnect" href="https://fonts.bunny.net">
                <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

                <!-- Scripts -->
                <x-importmap::tags />
            </head>
            <body class="font-sans antialiased">
                 <!-- ...  -->
            </body>
        </html>
        BLADE;

        $this->assertEquals($expected, (new ReplaceOrAppendTags())($contents));
    }

    /** @test */
    public function appends_to_before_closing_head_tag_when_vite_directive_is_missing()
    {
        $contents = <<<'BLADE'
        <!DOCTYPE html>
        <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="csrf-token" content="{{ csrf_token() }}">

                <title>{{ config('app.name', 'Laravel') }}</title>

                <!-- Fonts -->
                <link rel="preconnect" href="https://fonts.bunny.net">
                <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
            </head>
            <body class="font-sans antialiased">
                 <!-- ...  -->
            </body>
        </html>
        BLADE;

        $expected = <<<'BLADE'
        <!DOCTYPE html>
        <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <meta name="csrf-token" content="{{ csrf_token() }}">

                <title>{{ config('app.name', 'Laravel') }}</title>

                <!-- Fonts -->
                <link rel="preconnect" href="https://fonts.bunny.net">
                <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

                <x-importmap::tags />
            </head>
            <body class="font-sans antialiased">
                 <!-- ...  -->
            </body>
        </html>
        BLADE;

        $this->assertEquals($expected, (new ReplaceOrAppendTags())($contents));
    }
}
