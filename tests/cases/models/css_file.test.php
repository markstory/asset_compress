<?php
App::import('Model', 'AssetCompress.CssFile');

class CssFileTestCase extends CakeTestCase {
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
 * test @import processing
 *
 * @return void
 **/
	function testImportProcessing() {
		$this->CssFile->stripComments = false;
		$this->CssFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'css' . DS,
		);
		$result = $this->CssFile->process('has_import');
		$expected = <<<TEXT
* {
	margin:0;
	padding:0;
}
#nav {
	width:100%;
}
body {
	color:#f00;
	background:#000;
}
TEXT;
		$this->assertEqual($result, $expected);
	}
/**
 * test removal of comment blocks.
 *
 * @return void
 **/
	function testCommentRemoval() {
		$this->CssFile->stripComments = true;
		$this->CssFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'css' . DS,
		);
		$result = $this->CssFile->process('has_comments');
		$expected = <<<TEXT
body {
	color:#000;
}
TEXT;
		$this->assertEqual($result, $expected);
	}
}