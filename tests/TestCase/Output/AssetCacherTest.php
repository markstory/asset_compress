<?php
namespace AssetCompress\Test\TestCase\Output;

use AssetCompress\Output\AssetCacher;
use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use Cake\TestSuite\TestCase;

class AssetCacherTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->files = [
            new Local(APP . 'js/classes/base_class.js'),
            new Local(APP . 'js/classes/template.js'),
        ];
        $this->target = new AssetTarget(
            TMP . 'template.js',
            $this->files,
            [],
            [],
            true
        );
        $this->cacher = new AssetCacher(TMP);
        $this->themed = new AssetCacher(TMP, 'Modern');
    }

    public function testBuildFileName()
    {
        $result = $this->cacher->buildFileName($this->target);
        $this->assertEquals('template.js', $result);
    }

    public function testBuildFileNameThemed()
    {
        $result = $this->themed->buildFileName($this->target);
        $this->assertEquals('Modern-template.js', $result);
    }

    public function testWrite()
    {
        $result = $this->cacher->write($this->target, 'stuff');
        $this->assertFileExists(TMP . 'template.js');
        unlink(TMP . 'template.js');
    }

    public function testWriteThemed()
    {
        $result = $this->themed->write($this->target, 'stuff');
        $this->assertFileExists(TMP . 'Modern-template.js');
        unlink(TMP . 'Modern-template.js');
    }

    public function testReadThemed()
    {
        file_put_contents(TMP . 'Modern-template.js', 'contents');
        $result = $this->themed->read($this->target);
        $this->assertEquals('contents', $result);
        unlink(TMP . 'Modern-template.js');
    }

    public function testIsFreshOk()
    {
        file_put_contents(TMP . 'Modern-template.js', 'contents');
        $this->assertTrue($this->themed->isFresh($this->target));
    }

    public function testIsFreshOld()
    {
        file_put_contents(TMP . 'Modern-template.js', 'contents');
        // Simulate timestamps.
        touch(TMP . 'Modern-template.js', time() - 100);
        touch(APP . 'js/classes/template.js');
        $this->assertFalse($this->themed->isFresh($this->target));
    }

}
