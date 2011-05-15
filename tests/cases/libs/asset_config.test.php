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
		$this->assertTrue($config->debug);
	}

	function testFilters() {
		$config = new AssetConfig($this->testConfig);
		$result = $config->filters('js');
		$this->assertEqual(array('sprockets', 'yuicompressor'), $result);

		$result = $config->filters('js', 'libs.js');
		$this->assertEqual(array('sprockets', 'yuicompressor', 'uglify'), $result);

		$this->assertEqual(array(), $config->filters('nothing'));
	}

	function testFiles() {
		$config = new AssetConfig($this->testConfig);
		$result = $config->files('libs.js');
		$expected = array('jquery.js', 'mootools.js', 'class.js');
		$this->assertEqual($expected, $result);

		$result = $config->files('foo.bar.js');
		$expected = array('test.js');
		$this->assertEqual($expected, $result);

		$this->assertEqual(array(), $config->files('nothing here'));
	}

	function testPathConstantReplacement() {
		$config = new AssetConfig($this->testConfig);

		$result = $config->paths('css');
		$this->assertEqual(array(WWW_ROOT . 'css' . DS), $result);
		$this->assertEqual(array(), $config->paths('nothing'));
	}
}
