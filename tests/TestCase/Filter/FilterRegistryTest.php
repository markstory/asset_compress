<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\AssetFilter;
use AssetCompress\Filter\FilterRegistry;
use Cake\TestSuite\TestCase;

class FilterRegistryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->filters = [
            'noop' => new AssetFilter(),
            'simple' => new AssetFilter()
        ];
        $this->registry = new FilterRegistry($this->filters);
    }

    public function testContains()
    {
        $this->assertTrue($this->registry->contains('noop'));
        $this->assertFalse($this->registry->contains('missing'));
    }

    public function testAdd()
    {
        $filter = new AssetFilter();
        $this->assertNull($this->registry->add('new', $filter));
        $this->assertTrue($this->registry->contains('new'));
        $this->assertSame($filter, $this->registry->get('new'));
    }

    public function testGet()
    {
        $this->assertSame($this->filters['noop'], $this->registry->get('noop'));
        $this->assertNull($this->registry->get('missing'));
    }

    public function testRemove()
    {
        $this->assertTrue($this->registry->contains('noop'));
        $this->assertNull($this->registry->remove('noop'));
        $this->assertNull($this->registry->get('noop'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCollectionInvalidFilter()
    {
        $this->registry->collection(['noop', 'missing']);
    }

    public function testCollection()
    {
        $collection = $this->registry->collection(['noop', 'simple']);
        $this->assertInstanceOf('AssetCompress\Filter\FilterCollection', $collection);
        $this->assertCount(2, $collection);
    }
}
