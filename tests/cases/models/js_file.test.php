<?php

App::import('Model', 'AssetCompress.JsFile');

class JsFileTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$testFile = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
		$this->JsFile = new JsFile($testFile);
	}
/**
 * test the constuction and ini reading.
 *
 * @return void
 **/
	function testConstruction() {
		$testFile = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
		$JsFile = new JsFile($testFile);
		$this->assertTrue($JsFile->settings['stripComments']);
		$this->assertEqual($JsFile->settings['searchPaths'], array('/test/path', '/other/path'));
	}

/**
 * test Concatenating JS files together.
 *
 * @return void
 **/
	function testSimpleProcess() {
		$this->JsFile->settings['stripComments'] = false;
		$this->JsFile->settings['searchPaths'] = array(
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
		$this->JsFile->settings['stripComments'] = false;
		$this->JsFile->settings['searchPaths'] = array(
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
		$this->JsFile->settings['stripComments'] = false;
		$this->JsFile->settings['searchPaths'] = array(
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
		$this->JsFile->settings['stripComments'] = false;
		$this->JsFile->settings['searchPaths'] = array(
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
		$this->JsFile->settings['stripComments'] = true;
		$this->JsFile->settings['searchPaths'] = array(
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
 * try removing poorly formatted comments.
 *
 * @return void
 */
	function testPoorlyFormattedCommentRemoval() {
		$this->JsFile->settings['stripComments'] = true;
		$this->JsFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
		);
		$result = $this->JsFile->process('BadComments');
		$expected = <<<TEXT
(function($){function Foo(){this.bar=[];}})

function something() {
	return 1 + 1;
}
function two() {
	return 1 + 1;
}
TEXT;
		$this->assertEqual($expected, $result);
	}

/**
 * test that js files cache correctly.
 *
 * @return void
 */
	function testFileCaching() {
		$this->JsFile->settings['stripComments'] = true;
		$this->JsFile->settings['cacheFiles'] = true;
		$this->JsFile->settings['cacheFilePath'] = TMP . 'tests' . DS;
		$this->JsFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests/test_files/js/',
		);
		$contents = 'some javascript;';
		$result = $this->JsFile->cache('test_js_asset', $contents);
		$this->assertTrue($result);

		$time = time();
		$expected = <<<TEXT
/* asset_compress $time */
some javascript;
TEXT;
		$contents = file_get_contents(TMP . 'tests/test_js_asset');
		$this->assertEqual($contents, $expected);
		unlink(TMP . 'tests/test_js_asset');
	}

/**
 * test that files get timestampped when the setting is on.
 *
 * @return void
 */
	function testFileTimestampping() {
		$this->JsFile->clearBuildTimestamp();
		$this->JsFile->settings['stripComments'] = true;
		$this->JsFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
		);
		$this->JsFile->settings['cacheFiles'] = true;
		$this->JsFile->settings['cacheFilePath'] = TMP . 'tests' . DS;
		$this->JsFile->settings['timestamp'] = true;
		$contents ='some javascript';

		$result = $this->JsFile->cache('test_js_asset.js', $contents);
		$this->assertTrue($result);

		$time = time();
		$expected = <<<TEXT
/* asset_compress $time */
some javascript
TEXT;
		$result = file_get_contents(TMP . 'tests/test_js_asset.' . $time . '.js');
		$this->assertEqual($result, $expected);
		unlink(TMP . 'tests/test_js_asset.' . $time . '.js');

		$time = time();
		$result = $this->JsFile->cache('test_js_asset.' . $time . '.js', $contents);
		$this->assertTrue($result);

		$expected = <<<TEXT
/* asset_compress $time */
some javascript
TEXT;
		$result = file_get_contents(TMP . 'tests/test_js_asset.' . $time . '.js');
		$this->assertEqual($result, $expected);
		unlink(TMP . 'tests/test_js_asset.' . $time . '.js');
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