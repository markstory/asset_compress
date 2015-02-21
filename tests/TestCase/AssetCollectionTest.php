<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetTarget;
use AssetCompress\AssetCollection;
use Cake\TestSuite\TestCase;

class AssetCollectionTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->files = [
            new AssetTarget(TMP . 'one.js'),
            new AssetTarget(TMP . 'two.js'),
        ];
    }

    public function testAppend()
    {
        $add = new AssetTarget(TMP . 'three.js');
        $collection = new AssetCollection($this->files);
        $this->assertCount(2, $collection);

        $collection->append($add);
        $this->assertCount(3, $collection);
    }

    public function testContains()
    {
        $collection = new AssetCollection($this->files);

        $this->assertTrue($collection->contains('one.js'));
        $this->assertTrue($collection->contains('two.js'));
        $this->assertFalse($collection->contains('nope.css'));
    }

    public function testRemove()
    {
        $collection = new AssetCollection($this->files);

        $this->assertNull($collection->remove('one.js'));

        $this->assertFalse($collection->contains('one.js'));
        $this->assertNull($collection->get('one.js'));

        foreach ($collection as $item) {
            $this->assertNotEquals('one.js', $item->name());
        }
    }

    public function testGet()
    {
        $collection = new AssetCollection($this->files);

        $this->assertNull($collection->get('nope.js'));
        $this->assertSame($this->files[0], $collection->get('one.js'));
        $this->assertSame($this->files[1], $collection->get('two.js'));
    }
}
