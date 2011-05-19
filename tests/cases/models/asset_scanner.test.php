<?php

App::import('Model', 'AssetCompress.AssetScanner');

class AssetScannerTest extends CakeTestCase {
	function startTest() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = $this->_pluginPath . 'tests' . DS . 'test_files' . DS;
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

	function testConstantExpansion() {
		$paths = array(
			'WEBROOT/js',
			'APP/plugins/foo/webroot/'
		);
		$scanner = new AssetScanner($paths);
		$result = $scanner->paths();
		$expected = array(
			WWW_ROOT . 'js' . DS,
			APP . 'plugins' . DS . 'foo' . DS . 'webroot' . DS,
		);
		$this->assertEqual($expected, $result);
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

	function testExpandGlob() {
		$paths = array(
			$this->_testFiles . 'js' . DS . '*'
		);
		$scanner = new AssetScanner($paths);

		$result = $scanner->find('base_class.js');
		$expected = $this->_testFiles . 'js' . DS . 'classes' . DS . 'base_class.js';
		$this->assertEqual($expected, $result);
	}
}
