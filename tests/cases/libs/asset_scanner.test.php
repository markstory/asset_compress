<?php

App::import('Libs', 'AssetCompress.AssetScanner');

class AssetScannerTest extends CakeTestCase {

	function setUp() {
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
