<?php
namespace AssetCompress;

use AssetCompress\AssetConfig;
use AssetCompress\Factory;
use Cake\TestSuite\TestCase;

class FactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        AssetConfig::clearAllCachedKeys();
        $testConfig = APP . 'config' . DS . 'config.ini';
        $this->config = AssetConfig::buildFromIniFile($testConfig);
    }

    public function testFilterRegistry()
    {
        $factory = new Factory($this->config);
        $registry = $factory->filterRegistry();
        $this->assertTrue($registry->contains('Sprockets'));
        $this->assertTrue($registry->contains('YuiJs'));
        $this->assertTrue($registry->contains('CssMinFilter'));

        $filter = $registry->get('UglifyJs');
        $this->assertEquals('/path/to/uglify-js', $filter->settings()['path']);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot load filter "Derp"
     */
    public function testFilterRegistryMissingFilter()
    {
        $this->config->filterConfig('Derp', ['path' => '/test']);
        $factory = new Factory($this->config);
        $factory->filterRegistry();
    }
}
