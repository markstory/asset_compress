<?php
App::uses('AssetScanner', 'AssetCompress.Lib');

class AssetScannerTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = $this->_pluginPath . 'Test' . DS . 'test_files' . DS;
		$paths = array(
			$this->_testFiles . 'js' . DS,
			$this->_testFiles . 'js' . DS . 'classes' . DS
		);
		$this->Scanner = new AssetScanner($paths);
	}

	function testFind() {
		$result = $this->Scanner->find('base_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'classes' . DS . 'base_class.js';
		$this->assertEqual($expected, $result);

		$this->assertFalse($this->Scanner->find('does not exist'));
	}

	function testNormalizePaths() {
		$paths = array(
			$this->_testFiles . 'js',
			$this->_testFiles . 'js' . DS . 'classes'
		);
		$scanner = new AssetScanner($paths);

		$result = $scanner->find('base_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'classes' . DS . 'base_class.js';
		$this->assertEqual($expected, $result);
	}

	function testExpandStarStar() {
		$paths = array(
			$this->_testFiles . 'js' . DS . '**',
		);
		$scanner = new AssetScanner($paths);

		$result = $scanner->paths();
		$expected = array(
			$this->_testFiles . 'js' . DS,
			$this->_testFiles . 'js' . DS . 'classes' . DS,
			$this->_testFiles . 'js' . DS . 'secondary' . DS
		);
		$this->assertEqual($expected, $result);

		$result = $scanner->find('base_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'classes' . DS . 'base_class.js';
		$this->assertEqual($expected, $result);

		$result = $scanner->find('another_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'secondary' . DS . 'another_class.js';
		$this->assertEqual($expected, $result);
	}

	function testExpandGlob() {
		$paths = array(
			$this->_testFiles . 'js' . DS,
			$this->_testFiles . 'js' . DS . '*'
		);
		$scanner = new AssetScanner($paths);

		$result = $scanner->find('base_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'classes' . DS . 'base_class.js';
		$this->assertEqual($expected, $result);

		$result = $scanner->find('classes' . DS . 'base_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'classes' . DS . 'base_class.js';
		$this->assertEqual($expected, $result);
	}


	function testFindOtherExtension() {
		$paths = array(
			$this->_testFiles . 'css' . DS
		);
		$scanner = new AssetScanner($paths);
		$result = $scanner->find('other.less');
		$expected = $this->_testFiles . 'css' . DS . 'other.less';
		$this->assertEqual($expected, $result);
	}

	function testResolveThemePaths() {
		App::build(array(
			'View' => array($this->_testFiles . 'View' . DS)
		));
		$paths = array(
			$this->_testFiles . 'css' . DS
		);
		$scanner = new AssetScanner($paths, 'Blue');
		$result = $scanner->find('t:theme.css');
		$expected = $this->_testFiles . 'View' . DS . 'Themed' . DS . 'Blue' . DS . 'webroot' . DS . 'theme.css';
		$this->assertEqual($expected, $result);

		$result = $scanner->find('theme:theme.css');
		$this->assertEqual($expected, $result);
	}

	function testResolvePluginPaths() {
		App::build(array(
			'Plugin' => array($this->_testFiles . 'Plugin' . DS)
		));
		CakePlugin::load('TestAsset');
	
		$paths = array(
			$this->_testFiles . 'css' . DS
		);
		$scanner = new AssetScanner($paths);
		$result = $scanner->find('p:TestAsset:plugin.css');
		$expected = $this->_testFiles . 'Plugin' . DS . 'TestAsset' . DS . 'webroot' . DS . 'plugin.css';
		$this->assertEqual($expected, $result);

		$result = $scanner->find('plugin:TestAsset:plugin.css');
		$this->assertEqual($expected, $result);
	}

	function testIsRemote() {
		$paths = array(
			$this->_testFiles . 'css' . DS
		);
		$scanner = new AssetScanner($paths);
		$this->assertFalse($scanner->isRemote('/Users/markstory/cakephp'));
		$this->assertFalse($scanner->isRemote('C:\\Project\\cakephp'));
		$this->assertTrue($scanner->isRemote('http://example.com'));
	}
}
