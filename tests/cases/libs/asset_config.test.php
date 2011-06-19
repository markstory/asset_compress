<?php
App::import('Libs', 'AssetCompress.AssetConfig');

class AssetConfigTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';

		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
	}

	function testBuildFromIniFile() {
		$config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->assertTrue($config->get('js.timestamp'));
		$this->assertTrue($config->get('General.debug'));
	}

	function testFilters() {
		$result = $this->config->filters('js');
		$this->assertEqual(array('sprockets', 'jsyuicompressor'), $result);

		$result = $this->config->filters('js', 'libs.js');
		$this->assertEqual(array('sprockets', 'jsyuicompressor', 'uglify'), $result);

		$this->assertEqual(array(), $this->config->filters('nothing'));
	}

	function testSettingFilters() {
		$this->config->filters('js', null, array('uglify'));
		$this->assertEqual(array('uglify'), $this->config->filters('js'));
		$this->assertEqual(array('uglify'), $this->config->filters('js', 'libs.js'));

		$this->config->filters('js', 'libs.js', array('sprockets'));
		$this->assertEqual(array('uglify'), $this->config->filters('js'));
		$this->assertEqual(array('uglify', 'sprockets'), $this->config->filters('js', 'libs.js'));
	}

	function testFiles() {
		$result = $this->config->files('libs.js');
		$expected = array('jquery.js', 'mootools.js', 'class.js');
		$this->assertEqual($expected, $result);

		$result = $this->config->files('foo.bar.js');
		$expected = array('test.js');
		$this->assertEqual($expected, $result);

		$this->assertEqual(array(), $this->config->files('nothing here'));
	}

	function testSettingFiles() {
		$this->config->files('new_build.js', array('one.js', 'two.js'));

		$this->assertEqual(array('one.js', 'two.js'), $this->config->files('new_build.js'));
	}

	function testPathConstantReplacement() {
		$result = $this->config->paths('css');
		$this->assertEqual(array(WWW_ROOT . 'css' . DS), $result);
		$this->assertEqual(array(), $this->config->paths('nothing'));
	}

	function testPaths() {
		$this->config->paths('js', array('/path/to/files', 'WEBROOT/js'));
		$this->assertEqual(array('/path/to/files', WWW_ROOT . 'js'), $this->config->paths('js'));
	}

	function testAddTarget() {
		$this->config->addTarget('testing.js', array('one.js', 'two.js'));
		$this->assertEqual(array('one.js', 'two.js'), $this->config->files('testing.js'));
	}

	function testGetExt() {
		$this->assertEqual('js', $this->config->getExt('foo.bar.js'));
		$this->assertEqual('css', $this->config->getExt('something.less.css'));
	}

	function testCachePath() {
		$this->config->cachePath('js', 'WEBROOT/css_build');
		$this->assertEqual(WWW_ROOT . 'css_build', $this->config->cachePath('js'));
	}

	function testFilterConfig() {
		$result = $this->config->filterConfig('uglify');
		$expected = array('path' => '/path/to/uglify-js');
		$this->assertEqual($result, $expected);


		$this->config->filterConfig('sprockets', array('some' => 'value'));
		$this->assertEqual(array('some' => 'value'), $this->config->filterConfig('sprockets'));

		$this->assertEqual(array(), $this->config->filterConfig('imaginary'));
	}

	function testFilterConfigArray() {
		$this->config->filterConfig('sprockets', array('some' => 'value'));

		$result = $this->config->filterConfig(array('uglify', 'sprockets'));
		$expected = array(
			'sprockets' => array(
				'some' => 'value'
			),
			'uglify' => array(
				'path' => '/path/to/uglify-js'
			)
		);
		$this->assertEqual($result, $expected);
	}

	function testTargets() {
		$this->assertEqual(array(), $this->config->targets('fake'));
		$expected = array('libs.js', 'foo.bar.js');
		$result = $this->config->targets('js');
		$this->assertEqual($expected, $result);
	}
}
