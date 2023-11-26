<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tonysm\ImportmapLaravel\Actions\FixJsImportPaths;

class FixJsImportPathsTest extends TestCase
{
    protected string $tmpFolder;

    protected string $rootFolder;

    protected FixJsImportPaths $action;

    protected function setUp(): void
    {
        parent::setUp();

        $folder = (string) Str::uuid();
        File::ensureDirectoryExists($this->tmpFolder = __DIR__.implode(DIRECTORY_SEPARATOR, ['', 'tmp', 'fixing-paths', $folder]));
        File::cleanDirectory(dirname($this->tmpFolder));

        $this->action = new FixJsImportPaths(root: $this->rootFolder = __DIR__.implode(DIRECTORY_SEPARATOR, ['', 'stubs', 'fixing-paths']), output: $this->tmpFolder);
    }

    /** @test */
    public function fixes_imports()
    {
        $this->action->__invoke();

        // Root files...
        $this->assertTrue(File::exists($this->tmpFolder.DIRECTORY_SEPARATOR.'app.js'));
        $this->assertMatchesRegularExpression('#import ["\']bootstrap["\']'.PHP_EOL.'import ["\']libs["\']#', File::get($this->tmpFolder.DIRECTORY_SEPARATOR.'app.js'));
        $this->assertMatchesRegularExpression('#import axios from ["\']axios["\']#', File::get($this->tmpFolder.DIRECTORY_SEPARATOR.'bootstrap.js'));

        // Libs folders...
        $this->assertMatchesRegularExpression('#import ["\']libs/turbo["\']#', File::get($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'libs', 'index.js'])));
        $this->assertMatchesRegularExpression('#import ["\']controllers["\']#', File::get($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'libs', 'index.js'])));
        $this->assertFileEquals($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'libs', 'stimulus.js']), $this->rootFolder.implode(DIRECTORY_SEPARATOR, ['', 'libs', 'stimulus.js']));
        $this->assertFileEquals($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'libs', 'turbo.js']), $this->rootFolder.implode(DIRECTORY_SEPARATOR, ['', 'libs', 'turbo.js']));

        // Controllers folder...
        $this->assertMatchesRegularExpression('#import { application } from ["\']libs/stimulus["\']#', File::get($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'controllers', 'index.js'])));
        $this->assertMatchesRegularExpression('#import hello from ["\']controllers/hello_controller["\']#', File::get($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'controllers', 'index.js'])));
        $this->assertMatchesRegularExpression('#import { Controller } from ["\']@hotwired/stimulus["\']#', File::get($this->tmpFolder.implode(DIRECTORY_SEPARATOR, ['', 'controllers', 'hello_controller.js'])));
    }
}
