<?php

class JsFileTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->JsFile = ClassRegistry::init('AssetCompress.JsFile');
		$this->_pluginPath = $this->_findPlugin();
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
/**
 * test Concatenating JS files together.
 *
 * @return void
 **/
	function testSimpleProcess() {
		$this->JsFile->stripComments = false;
		$this->JsFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS,
		);
		$result = $this->JsFile->process('Template');
		$expected = <<<TEXT
var BaseClass = new Class({

});
var Template = new Class({

});
TEXT;
		$this->assertEqual($result, $expected);

		$result = $this->JsFile->process('nested_class');
		$expected = <<<TEXT
var BaseClass = new Class({

});
var BaseClassTwo = BaseClass.extend({

});
// Remove me
// remove me too
var NestedClass = BaseClassTwo.extend({

});
TEXT;
		$this->assertEqual($result, $expected);
	}
/**
 * endTest
 *
 * @return void
 **/
	function endTest() {
		unset($this->JsFile);
		ClassRegistry::flush();
	}
}