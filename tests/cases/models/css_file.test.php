<?php
App::import('Model', 'AssetCompress.CssFile');

class CssFileTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->CssFile = new CssFile();
		$this->_pluginPath = $this->_findPlugin();
	}
/**
 * test the constuction and ini reading.
 *
 * @return void
 **/
	function testConstruction() {
		$testFile = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
		$CssFile = new CssFile($testFile);
		$this->assertTrue($CssFile->stripComments);
		$this->assertEqual($CssFile->searchPaths, array('/test/css', '/test/css/more'));
	}
/**
 * find the asset_compress path
 *
 * @return void
 **/
	function _findPlugin() {
		$paths = Configure::read('pluginPaths');
		foreach ($paths as $path) {
			if (is_dir($path . 'asset_compress')) {
				return $path . 'asset_compress' . DS;
			}
		}
		throw new Exception('Could not find my directory, bailing hard!');
	}
}