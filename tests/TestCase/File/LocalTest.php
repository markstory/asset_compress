<?php
namespace AssetCompress\Test\TestCase\File;

use AssetCompress\File\Local;
use Cake\TestSuite\TestCase;

class LocalTest extends TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testErrorOnInvalidFile()
    {
        $file = new Local('/invalid');
    }

    public function testName()
    {
        $file = new Local(__FILE__);
        $this->assertEquals('LocalTest.php', $file->name());
    }

    public function testContents()
    {
        $file = new Local(__FILE__);
        $this->assertContains('LocalTest extends TestCase', $file->contents());
    }

    public function testModifiedTime()
    {
        $file = new Local(__FILE__);
        $this->assertGreaterThan(0, $file->modifiedTime());
    }
}
