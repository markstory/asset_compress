<?php
App::import('Libs', 'AssetCompress.AssetConfig');

class AssetConfigTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
	}

	function testReadIniFile() {
		$config = new AssetConfig($this->testConfig);
		$this->assertTrue($config->js['timestamp']);
	}

	function testFilters() {
		$config = new AssetConfig($this->testConfig);
		$result = $config->filters('js');
		$this->assertEqual(array('sprockets', 'yuicompressor'), $result);

		$result = $config->filters('js', 'libs.js');
		$this->assertEqual(array('sprockets', 'yuicompressor', 'uglify'), $result);

		$this->assertEqual(array(), $config->filters('nothing'));
	}
}
