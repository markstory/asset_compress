<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetTarget;
use AssetCompress\AssetCollection;
use AssetCompress\Factory;
use AssetCompress\AssetConfig;
use Cake\TestSuite\TestCase;

class AssetCollectionTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $config = new AssetConfig([], [
            'TEST_FILES/' => APP,
        ]);
        $config->load(APP . 'config/integration.ini');
        $this->factory = new Factory($config);
    }

    public function testAppend()
    {
        $add = new AssetTarget(TMP . 'three.js');
        $collection = new AssetCollection(['libs.js', 'all.css'], $this->factory);
        $this->assertCount(2, $collection);

        $collection->append($add);
        $this->assertCount(3, $collection);
    }

    public function testContains()
    {
        $collection = new AssetCollection(['libs.js', 'all.css'], $this->factory);

        $this->assertTrue($collection->contains('libs.js'));
        $this->assertTrue($collection->contains('all.css'));
        $this->assertFalse($collection->contains('nope.css'));
    }

    public function testRemove()
    {
        $collection = new AssetCollection(['libs.js', 'all.css'], $this->factory);

        $this->assertNull($collection->remove('libs.js'));

        $this->assertFalse($collection->contains('libs.js'));
        $this->assertNull($collection->get('libs.js'));

        foreach ($collection as $item) {
            $this->assertNotEquals('libs.js', $item->name());
        }
    }

    public function testGet()
    {
        $collection = new AssetCollection(['libs.js', 'all.css'], $this->factory);

        $this->assertNull($collection->get('nope.js'));
        $this->assertInstanceOf('AssetCompress\AssetTarget', $collection->get('libs.js'));
    }
}
