<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetConfig;
use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * AssetConfig test for plugins
 */
class AssetConfigPluginIniTest extends TestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'engine' => 'File'
		));

		$this->_testFiles = APP;
		$this->testConfig = $this->_testFiles . 'config' . DS . 'config.ini';
		$this->_themeConfig = $this->_testFiles . 'config' . DS . 'themed.ini';

		Plugin::load('TestAssetIni');

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
	}

/**
 * teardown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload('TestAssetIni');
	}

	public function testPluginIni() {
		$result = $this->config->files('TestAssetIni.libs.js');
		$expected = array('classes/base_class.js', 'classes/template.js');
		$this->assertEquals($expected, $result);

		$result = $this->config->files('TestAssetIni.foo.bar.js');
		$expected = array('test.js');
		$this->assertEquals($expected, $result);

		$result = $this->config->files('TestAssetIni.all.css');
		$expected = array('layout.css');
		$this->assertEquals($expected, $result);
	}

	public function testIniTargets() {
		$expected = array(
			'libs.js',
			'foo.bar.js',
			'new_file.js',
			'TestAssetIni.libs.js',
			'TestAssetIni.foo.bar.js',
			'TestAssetIni.overridable_scripts.js'
		);
		$result = $this->config->targets('js');
		$this->assertEquals($expected, $result);

		$expected = array(
			'all.css',
			'pink.css',
			'TestAssetIni.all.css',
			'TestAssetIni.overridable_styles.css'
		);
		$result = $this->config->targets('css');
		$this->assertEquals($expected, $result);
	}

	public function testLocalPluginConfig() {
		$result = $this->config->files('TestAssetIni.overridable_scripts.js');
		$expected = array('base.js', 'local_script.js');
		$this->assertEquals($expected, $result);

		$result = $this->config->files('TestAssetIni.overridable_styles.css');
		$expected = array('local_style.css');
		$this->assertEquals($expected, $result);
	}
}
