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

	function testSettingFilters() {
		$config = new AssetConfig($this->testConfig);
		$config->filters('js', null, array('uglify'));
		$this->assertEqual(array('uglify'), $config->filters('js'));
		$this->assertEqual(array('uglify'), $config->filters('js', 'libs.js'));

		$config->filters('js', 'libs.js', array('sprockets'));
		$this->assertEqual(array('uglify'), $config->filters('js'));
		$this->assertEqual(array('uglify', 'sprockets'), $config->filters('js', 'libs.js'));
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

	function testPaths() {
		$config = new AssetConfig($this->testConfig);
		$config->paths('js', array('/path/to/files'));
		$this->assertEqual(array('/path/to/files'), $config->paths('js'));
	}

	function testAddTarget() {
		$config = new AssetConfig($this->testConfig);
		$config->addTarget('testing.js', array('one.js', 'two.js'));
		$this->assertEqual(array('one.js', 'two.js'), $config->files('testing.js'));
	}

	function testGetExt() {
		$config = new AssetConfig($this->testConfig);

		$this->assertEqual('js', $config->getExt('foo.bar.js'));
		$this->assertEqual('css', $config->getExt('something.less.css'));
	}

}
