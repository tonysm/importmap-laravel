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
        File::ensureDirectoryExists($this->tmpFolder = __DIR__.'/tmp/fixing-paths/'.$folder);
        File::cleanDirectory(dirname($this->tmpFolder));

        $this->action = new FixJsImportPaths(root: $this->rootFolder = __DIR__.'/stubs/fixing-paths', output: $this->tmpFolder);
    }

    /** @test */
    public function fixes_imports()
    {
        $this->action->__invoke();

        // Root files...
        $this->assertTrue(File::exists($this->tmpFolder.'/app.js'));
        $this->assertMatchesRegularExpression('#import ["\']bootstrap["\']#', File::get($this->tmpFolder.'/app.js'));
        $this->assertMatchesRegularExpression('#import axios from ["\']axios["\']#', File::get($this->tmpFolder.'/bootstrap.js'));

        // Libs folders...
        $this->assertMatchesRegularExpression('#import ["\']libs/turbo["\']#', File::get($this->tmpFolder.'/libs/index.js'));
        $this->assertMatchesRegularExpression('#import ["\']controllers["\']#', File::get($this->tmpFolder.'/libs/index.js'));
        $this->assertFileEquals($this->tmpFolder.'/libs/stimulus.js', $this->rootFolder.'/libs/stimulus.js');
        $this->assertFileEquals($this->tmpFolder.'/libs/turbo.js', $this->rootFolder.'/libs/turbo.js');

        // Controllers folder...
        $this->assertMatchesRegularExpression('#import { application } from ["\']libs/stimulus["\']#', File::get($this->tmpFolder.'/controllers/index.js'));
        $this->assertMatchesRegularExpression('#import hello from ["\']controllers/hello_controller["\']#', File::get($this->tmpFolder.'/controllers/index.js'));
        $this->assertMatchesRegularExpression('#import { Controller } from ["\']@hotwired/stimulus["\']#', File::get($this->tmpFolder.'/controllers/hello_controller.js'));
    }
}
