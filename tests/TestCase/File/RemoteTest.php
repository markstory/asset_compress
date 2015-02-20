<?php
namespace AssetCompress\Test\TestCase\File;

use AssetCompress\File\Remote;
use Cake\TestSuite\TestCase;

class RemoteTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $file = file_get_contents('http://google.com');
        $this->skipIf(strlen($file) === 0, 'Fetching file failed');
    }

    public function testName()
    {
        $file = new Remote('http://google.com');
        $this->assertEquals('http://google.com', $file->name());
    }

    public function testContents()
    {
        $file = new Remote('http://google.com');
        $this->assertContains('html', $file->contents());
    }

    public function testModifiedTime()
    {
        $file = new Remote('http://google.com');
        $this->assertGreaterThan(0, $file->modifiedTime());
    }
}
