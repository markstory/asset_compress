<?php
declare(strict_types=1);

namespace AssetCompress;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use MiniAsset\AssetConfig;

class FactoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $testConfig = APP . 'config' . DS . 'config.ini';
        $this->config = AssetConfig::buildFromIniFile($testConfig);

        $this->integrationFile = APP . 'config' . DS . 'integration.ini';
        $this->themedFile = APP . 'config' . DS . 'themed.ini';
        $this->pluginFile = APP . 'config' . DS . 'plugins.ini';
        $this->overrideFile = APP . 'config' . DS . 'overridable.local.ini';
        $this->timestampFile = APP . 'config' . DS . 'timestamp.ini';
    }

    public function testFilterRegistry()
    {
        $factory = new Factory($this->config);
        $registry = $factory->filterRegistry();
        $this->assertTrue($registry->contains('Sprockets'));
        $this->assertTrue($registry->contains('YuiJs'));
        $this->assertTrue($registry->contains('CssMinFilter'));

        $filter = $registry->get('Uglifyjs');
        $this->assertEquals('/path/to/uglify-js', $filter->settings()['path']);
    }

    public function testFilterRegistryPreferPluginFilter()
    {
        $factory = new Factory($this->config);
        $registry = $factory->filterRegistry();
        $this->assertTrue($registry->contains('Sprockets'));
        $this->assertInstanceOf('AssetCompress\Filter\Sprockets', $registry->get('Sprockets'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Cannot load filter "Derp"
     */
    public function testFilterRegistryMissingFilter()
    {
        $this->config->filters('js', ['Derp']);
        $this->config->filterConfig('Derp', ['path' => '/test']);
        $factory = new Factory($this->config);
        $factory->filterRegistry();
    }

    public function testAssetCollection()
    {
        $config = AssetConfig::buildFromIniFile($this->integrationFile, [
            'TEST_FILES' => APP,
            'WEBROOT' => TMP,
        ]);
        $factory = new Factory($config);
        $collection = $factory->assetCollection();

        $this->assertCount(4, $collection);
        $this->assertTrue($collection->contains('libs.js'));
        $this->assertTrue($collection->contains('foo.bar.js'));
        $this->assertTrue($collection->contains('all.css'));
        $this->assertTrue($collection->contains('blue-app.js'));

        $asset = $collection->get('libs.js');
        $this->assertCount(2, $asset->files(), 'Not enough files');
        $paths = [
            APP . 'js' . DS,
            APP . 'js' . DS . 'classes' . DS,
            APP . 'js' . DS . 'secondary' . DS,
        ];
        $this->assertEquals($paths, $asset->paths(), 'Paths are incorrect');
        $this->assertEquals(['Sprockets'], $asset->filterNames(), 'Filters are incorrect');
        $this->assertFalse($asset->isThemed(), 'Themed is wrong');
        $this->assertEquals('libs.js', $asset->name(), 'Asset name is wrong');
        $this->assertEquals('js', $asset->ext(), 'Asset ext is wrong');
        $this->assertEquals(str_replace(DS, '/', TMP . 'cache_js'), str_replace(DS, '/', $asset->outputDir()), 'Asset path is wrong');
        $this->assertEquals(
            str_replace(DS, '/', TMP . 'cache_js/libs.js'),
            str_replace(DS, '/', $asset->path()),
            'Asset path is wrong'
        );
    }

    /**
     * Test that themed assets are built correctly.
     *
     * @return void
     */
    public function testAssetCollectionThemed()
    {
        $this->loadPlugins(['Red']);
        $config = AssetConfig::buildFromIniFile($this->themedFile, [
            'TEST_FILES' => APP,
            'WEBROOT' => TMP,
        ]);
        $config->theme('Red');

        $factory = new Factory($config);
        $collection = $factory->assetCollection();

        $this->assertTrue($collection->contains('themed.css'));
        $asset = $collection->get('themed.css');

        $this->assertTrue($asset->isThemed());

        $files = $asset->files();
        $this->assertCount(1, $files);
        $this->assertEquals(
            str_replace(DS, '/', APP . 'Plugin/Red/webroot/theme.css'),
            str_replace(DS, '/', $files[0]->path())
        );
    }

    /**
     * Test that plugin assets are built correctly.
     *
     * @return void
     */
    public function testAssetCollectionPlugins()
    {
        $this->loadPlugins(['TestAsset']);
        $config = AssetConfig::buildFromIniFile($this->pluginFile, [
            'TEST_FILES' => APP,
            'WEBROOT' => TMP,
        ]);
        $factory = new Factory($config);
        $collection = $factory->assetCollection();

        $this->assertTrue($collection->contains('plugins.js'));
        $this->assertTrue($collection->contains('plugins.css'));

        $asset = $collection->get('plugins.js');
        $this->assertCount(1, $asset->files());
        $this->assertEquals(
            str_replace('/', DS, APP . 'Plugin/TestAsset/webroot/plugin.js'),
            $asset->files()[0]->path()
        );

        $asset = $collection->get('plugins.css');
        $files = $asset->files();
        $this->assertCount(2, $files);
        $this->assertEquals(
            APP . 'css' . DS . 'nav.css',
            $asset->files()[0]->path()
        );
    }

    public function testAssetCreationWithAdditionalPath()
    {
        $config = AssetConfig::buildFromIniFile($this->overrideFile, [
            'WEBROOT' => APP,
        ]);
        $factory = new Factory($config);
        $collection = $factory->assetCollection();
        $asset = $collection->get('libs.js');

        $files = $asset->files();
        $this->assertCount(3, $files);
        $this->assertEquals(
            APP . 'js' . DS . 'base.js',
            $files[0]->path()
        );
        $this->assertEquals(
            APP . 'js' . DS . 'library_file.js',
            $files[1]->path()
        );
        $this->assertEquals(
            APP . 'js' . DS . 'classes' . DS . 'base_class.js',
            $files[2]->path()
        );
    }

    public function testWriter()
    {
        $config = AssetConfig::buildFromIniFile($this->integrationFile, [
            'TEST_FILES' => APP,
            'WEBROOT' => TMP,
        ]);
        $config->theme('Red');
        $config->set('js.timestamp', true);
        $factory = new Factory($config);
        $writer = $factory->writer();

        $expected = [
            'timestamp' => [
                'js' => true,
                'css' => false,
            ],
            'path' => TMP,
            'theme' => 'Red',
        ];
        $this->assertEquals($expected, $writer->config());
    }

    public function testWriterWithTimestampPath()
    {
        $config = AssetConfig::buildFromIniFile($this->timestampFile, [
            'TEST_FILES' => APP,
            'WEBROOT' => TMP,
        ]);
        $factory = new Factory($config);
        $writer = $factory->writer();

        $expected = [
            'timestamp' => [
                'js' => true,
                'css' => false,
            ],
            'path' => TMP . 'timestamp' . DIRECTORY_SEPARATOR,
            'theme' => '',
        ];
        $this->assertEquals($expected, $writer->config());
    }

    public function testCompiler()
    {
        $config = AssetConfig::buildFromIniFile($this->overrideFile, [
            'WEBROOT' => APP,
        ]);
        $factory = new Factory($config);
        $compiler = $factory->compiler();
        $this->assertAttributeEquals(true, 'debug', $compiler);

        Configure::write('debug', false);
        $compiler = $factory->compiler();
        $this->assertAttributeEquals(false, 'debug', $compiler);
    }

    public function testCachedCompiler()
    {
        $config = AssetConfig::buildFromIniFile($this->overrideFile, [
            'WEBROOT' => APP,
        ]);
        $factory = new Factory($config);
        $compiler = $factory->cachedCompiler();

        $innerCompiler = $this->readAttribute($compiler, 'compiler');
        $this->assertAttributeEquals(true, 'debug', $innerCompiler);

        $cacher = $this->readAttribute($compiler, 'cacher');
        $this->assertAttributeEquals(CACHE . 'asset_compress' . DS, 'path', $cacher);
    }
}
