<?php
App::uses('AssetConfig', 'AssetCompress.Lib');

/**
 * AssetConfig test for plugins
 */
class AssetConfigPluginIniTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'engine' => 'File'
		));

		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = App::pluginPath('AssetCompress') . 'Test' . DS . 'test_files' . DS;
		$this->testConfig = $this->_testFiles . 'Config' . DS . 'config.ini';
		$this->_themeConfig = $this->_testFiles . 'Config' . DS . 'themed.ini';

		App::build(array(
			'Plugin' => array($this->_testFiles . 'Plugin' . DS)
		));
		CakePlugin::load('TestAssetIni');

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
	}

	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload('TestAssetIni');
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
		$expected = array('libs.js', 'foo.bar.js', 'new_file.js', 'TestAssetIni.libs.js', 'TestAssetIni.foo.bar.js');
		$result = $this->config->targets('js');
		$this->assertEquals($expected, $result);

		$expected = array('all.css', 'pink.css', 'TestAssetIni.all.css');
		$result = $this->config->targets('css');
		$this->assertEquals($expected, $result);
	}

}
