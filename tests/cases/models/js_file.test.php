<?php

App::import('Model', 'AssetCompress.JsFile');

class JsFileTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->JsFile = new JsFile();
		$this->_pluginPath = $this->_findPlugin();
	}
/**
 * test the constuction and ini reading.
 *
 * @return void
 **/
	function testConstruction() {
		$testFile = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
		$JsFile = new JsFile($testFile);
		$this->assertTrue($JsFile->stripComments);
		$this->assertEqual($JsFile->searchPaths, array('/test/path', '/other/path'));
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
 * test that files included more than once only show up once.
 *
 * @return void
 **/
	function testDoubleInclusions() {
		$this->JsFile->stripComments = false;
		$this->JsFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS,
		);
		$result = $this->JsFile->process('DoubleInclusion');
		$expected = <<<TEXT
var BaseClass = new Class({

});
var BaseClassTwo = BaseClass.extend({

});
var DoubleInclusion = new Class({

});
TEXT;
		$this->assertEqual($result, $expected);
	}
/**
 * test gluing more than one thing together
 *
 * @return void
 **/
	function testMoreThanOneArg() {
		$this->JsFile->stripComments = false;
		$this->JsFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS,
		);
		$result = $this->JsFile->process(array('Template', 'DoubleInclusion'));
		$expected = <<<TEXT
var BaseClass = new Class({

});
var Template = new Class({

});
var BaseClassTwo = BaseClass.extend({

});
var DoubleInclusion = new Class({

});
TEXT;
		$this->assertEqual($result, $expected);
	}
/**
 * test that <foo> scans all search paths for a suitable file. Unlike "foo" which only scans the 
 * current dir.
 *
 * @return void
 **/
	function testAngleBracketScanning() {
		$this->JsFile->stripComments = false;
		$this->JsFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS,
		);
		$result = $this->JsFile->process('Slideshow');
		$expected = <<<TEXT
/*!
 this comment will stay
*/
// this comment should be removed
function test(thing) {
	/* this comment will be removed */
	// I'm gone
	thing.doStuff(); //I get to stay
	return thing;
}
var AnotherClass = Class.extend({

});
var Slideshow = new Class({

});
TEXT;
		$this->assertEqual($result, $expected);
	}
/**
 * test Stripping comments out.
 *
 * @return void
 **/
	function testCommentStripping() {
		$this->JsFile->stripComments = true;
		$this->JsFile->searchPaths = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
		);
		$result = $this->JsFile->process('LibraryFile');
		$expected = <<<TEXT
/*!
 this comment will stay
*/
function test(thing) {
	thing.doStuff(); //I get to stay
	return thing;
}
TEXT;
		$this->assertEqual($result, $expected);

		$result = $this->JsFile->process('lots_of_comments');
		$expected = <<<TEXT
/*!
Important comment
*/
All
be
not
here
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