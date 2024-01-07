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

        $this->singleQuoteFilePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'single-quote-importmap-'.Str::random(8).'.php';
        copy(__DIR__.implode(DIRECTORY_SEPARATOR, ['', 'fixtures', 'npm', 'single-quote-importmap.php']), $this->singleQuoteFilePath);
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

    /** @test */
    public function removes_url_unsafe_elements()
    {
        $this->assertStringContainsString(
            '#lorem/buffer.js',
            $this->packager->vendoredPinFor('#lorem/buffer.js', '/js/vendor/#lorem/buffer.js.js'),
        );

        $this->assertStringContainsString(
            '/js/vendor/--lorem--buffer.js',
            $this->packager->vendoredPinFor('#lorem/buffer.js', '/js/vendor/#lorem/buffer.js.js'),
        );
    }
}
