<?php

namespace Tonysm\ImportmapLaravel\Tests;

use Illuminate\Support\Str;
use Tonysm\ImportmapLaravel\Packager;

class PackagerSingleQuoteTest extends TestCase
{
    private Packager $packager;

    private string $singleQuoteFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->singleQuoteFilePath = sys_get_temp_dir().'/single-quote-importmap-'.Str::random(8).'.php';
        file_put_contents($this->singleQuoteFilePath, file_get_contents(__DIR__.'/fixtures/npm/single-quote-importmap.php'));
        $this->packager = new Packager($this->singleQuoteFilePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->singleQuoteFilePath)) {
            @unlink($this->singleQuoteFilePath);
        }

        parent::tearDown();
    }

    /** @test */
    public function packaged_with_single_quotes()
    {
        $this->assertTrue($this->packager->packaged('md5'));
        $this->assertFalse($this->packager->packaged('md5-extension'));
    }

    /** @test */
    public function remove_package_with_single_quote()
    {
        $this->packager->remove('md5');

        $this->assertFalse($this->packager->packaged('md5'));
    }
}
