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

    #[\PHPUnit\Framework\Attributes\Test]
    public function packaged_with_single_quotes(): void
    {
        $this->assertTrue($this->packager->packaged('md5'));
        $this->assertFalse($this->packager->packaged('md5-extension'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function remove_package_with_single_quote(): void
    {
        $this->packager->remove('md5');

        $this->assertFalse($this->packager->packaged('md5'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function removes_url_unsafe_elements(): void
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
