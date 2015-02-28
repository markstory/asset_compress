<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetWriter;
use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use Cake\TestSuite\TestCase;

class AssetWriterTest extends TestCase
{
    protected $files = [];

    public function setUp()
    {
        parent::setUp();
        $this->files = [
            new Local(APP . 'js/library_file.js'),
            new Local(APP . 'js/bad_comments.js'),
        ];
        $this->target = new AssetTarget(TMP . 'test.js', $this->files, [], [], true);
        $this->writer = new AssetWriter(['js' => false, 'css' => false], TMP);
    }

    public function testWrite()
    {
        $result = $this->writer->write($this->target, 'Some content');
        $this->assertNotEquals($result, false);
        $contents = file_get_contents(TMP . 'test.js');
        $this->assertEquals('Some content', $contents);
        unlink(TMP . 'test.js');
    }

    public function testWriteTimestamp()
    {
        $writer = new AssetWriter(['js' => true, 'css' => false], TMP);

        $now = time();
        $writer->setTimestamp($this->target, $now);
        $writer->write($this->target, 'Some content');

        $contents = file_get_contents(TMP . 'test.v' . $now . '.js');
        $this->assertEquals('Some content', $contents);
        unlink(TMP . 'test.v' . $now . '.js');
    }

    public function testIsFreshNoBuild()
    {
        $this->assertFalse($this->writer->isFresh($this->target));
    }

    public function testIsFreshSuccess()
    {
        touch(TMP . '/test.js');

        $this->assertTrue($this->writer->isFresh($this->target));
        unlink(TMP . '/test.js');
    }

    public function testThemeFileSaving()
    {
        $writer = new AssetWriter(['js' => false, 'css' => false], TMP, 'blue');

        $writer->write($this->target, 'theme file.');
        $contents = file_get_contents(TMP . 'blue-themed.css');
        $this->assertEquals('theme file.', $contents);
    }

    public function testGetSetTimestamp()
    {
        $writer = new AssetWriter(['js' => true, 'css' => false], TMP);
        $time = time();
        $writer->setTimestamp($this->target, $time);
        $result = $writer->getTimestamp($this->target);
        $this->assertEquals($time, $result);
    }

    public function testGetSetTimestampWithTimestampOff()
    {
        $writer = new AssetWriter(['js' => false, 'css' => false], TMP);
        $result = $writer->getTimestamp($this->target);
        $this->assertFalse($result);
    }

    public function testBuildFileNameTheme()
    {
        $writer = new AssetWriter(['js' => false, 'css' => false], TMP, 'blue');

        $result = $writer->buildFileName($this->target);
        $this->assertEquals('blue-test.js', $result);
    }

    public function testBuildFileNameTimestampNoValue()
    {
        $writer = new AssetWriter(['js' => true, 'css' => false], TMP);

        $time = time();
        $result = $writer->buildFileName($this->target);
        $this->assertEquals('test.v' . $time . '.js', $result);
    }

    public function testTimestampFromCache()
    {
        $writer = new AssetWriter(['js' => true, 'css' => false], TMP);

        $time = time();
        $writer->buildFilename($this->target);

         // delete the file so we know we hit the cache.
        unlink(TMP . AssetWriter::BUILD_TIME_FILE);

        $result = $writer->buildFilename($this->target);
        $this->assertEquals('test.v' . $time . '.js', $result);
    }

    public function testInvalidateAndFinalizeBuildTimestamp()
    {
        $writer = new AssetWriter(['js' => true, 'css' => false], TMP);

        $cacheName = $writer->buildCacheName($this->target);
        $writer->invalidate($this->target);
        $invalidatedCacheName = $writer->buildCacheName($this->target);
        $this->assertNotEquals($cacheName, $invalidatedCacheName);

        $time = $writer->getTimestamp($this->target);

        $writer->finalize($this->target);
        $finalizedCacheName = $writer->buildCacheName($this->target);
        $this->assertEquals($cacheName, $finalizedCacheName);

        $finalizedTime = $writer->getTimestamp($this->target);
        $this->assertEquals($time, $finalizedTime);
    }
}
