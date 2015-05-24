<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\Config\ConfigFinder;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * Test for finding and loading config files.
 */
class ConfigFinderTest extends TestCase
{

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_testFiles = APP;
        $this->testConfig = $this->_testFiles . 'config' . DS . 'config.ini';
        $this->_themeConfig = $this->_testFiles . 'config' . DS . 'themed.ini';

        Plugin::load('TestAssetIni');
    }

    /**
     * teardown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload('TestAssetIni');
    }

    public function testPluginIni()
    {
        $configFinder = new ConfigFinder();
        $config = $configFinder->loadAll($this->testConfig);

        $result = $config->files('TestAssetIni.libs.js');
        $expected = array('classes/base_class.js', 'classes/template.js');
        $this->assertEquals($expected, $result);

        $result = $config->files('TestAssetIni.foo.bar.js');
        $expected = array('bad_comments.js');
        $this->assertEquals($expected, $result);

        $result = $config->files('TestAssetIni.all.css');
        $expected = array('background.css');
        $this->assertEquals($expected, $result);
    }

    public function testIniTargets()
    {
        $configFinder = new ConfigFinder();
        $config = $configFinder->loadAll($this->testConfig);

        $expected = array(
            'libs.js',
            'foo.bar.js',
            'new_file.js',
            'all.css',
            'pink.css',
            'TestAssetIni.libs.js',
            'TestAssetIni.foo.bar.js',
            'TestAssetIni.all.css',
            'TestAssetIni.overridable_scripts.js',
            'TestAssetIni.overridable_styles.css'
        );
        $result = $config->targets();
        $this->assertEquals($expected, $result);
    }

    public function testLocalPluginConfig()
    {
        $configFinder = new ConfigFinder();
        $config = $configFinder->loadAll($this->testConfig);

        $result = $config->files('TestAssetIni.overridable_scripts.js');
        $expected = array('base.js', 'local_script.js');
        $this->assertEquals($expected, $result);

        $result = $config->files('TestAssetIni.overridable_styles.css');
        $expected = array('local_style.css');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test local configuration files.
     *
     * @return void
     */
    public function testLocalConfig()
    {
        $ini = dirname($this->testConfig) . DS . 'overridable.ini';
        $configFinder = new ConfigFinder();
        $config = $configFinder->loadAll($ini);

        $this->assertEquals('', $config->general('cacheConfig'));
        $this->assertEquals(1, $config->general('alwaysUseController'));

        $this->assertEquals('', $config->get('js.timestamp'));

        $result = $config->paths('js');
        $result = str_replace('/', DS, $result);
        $expectedJsPaths = array(WWW_ROOT . 'js' . DS . '*', WWW_ROOT . 'js_local' . DS . '*');
        $this->assertEquals($expectedJsPaths, $result);

        $this->assertEquals(WWW_ROOT . 'cache_js/', $config->cachePath('js'));

        $result = $config->filters('js');
        $expectedJsFilters = array('Sprockets', 'YuiJs');
        $this->assertEquals($expectedJsFilters, $result);

        $result = $config->filterConfig('YuiJs');
        $this->assertEquals(array('path' => '/path/to/local/yuicompressor'), $result);

        $result = $config->filterConfig('Uglifyjs');
        $this->assertEquals(array('path' => '/path/to/uglify-js'), $result);

        $result = $config->paths('js', 'libs.js');
        $result = str_replace('/', DS, $result);
        $expectedJsPaths[] = WWW_ROOT . 'js' . DS . 'classes' . DS . '*';
        $this->assertEquals($expectedJsPaths, $result);

        $result = $config->files('libs.js');
        $expected = array('base.js', 'library_file.js', 'base_class.js');
        $this->assertEquals($expected, $result);

        $result = $config->targetFilters('libs.js');
        $expectedJsFilters[] = 'Uglifyjs';
        $this->assertEquals($expectedJsFilters, $result);
    }

}
