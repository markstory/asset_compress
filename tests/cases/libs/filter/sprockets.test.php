<?php
App::import('Lib', 'AssetCompress.filter/Sprockets');

class SprocketsTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_jsDir = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS;

		$this->filter = new Sprockets();
		$settings = array(
			'paths' => array(
				$this->_jsDir,
				$this->_jsDir . 'classes' . DS,
			)
		);
		$this->filter->settings($settings);
	}

	function testInputSimple() {
		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'template.js');

		$result = $this->filter->input('template.js', $content);
		$expected = <<<TEXT
var BaseClass = new Class({

});
var Template = new Class({

});

TEXT;
		$this->assertEqual($result, $expected);
	 }

	function testInputWithRecursion() {
		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'nested_class.js');
		$result = $this->filter->input('nested_class.js', $content);
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

	function testDoubleInclusion() {
		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'double_inclusion.js');
		$result = $this->filter->input('double_inclusion.js', $content);
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
 * test that <foo> scans all search paths for a suitable file. Unlike "foo" which only scans the
 * current dir.
 *
 * @return void
 **/
	function testAngleBracketScanning() {
		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'slideshow.js');
		$result = $this->filter->input('slideshow.js', $content);
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
 * The unique dependency counter should persist across input() calls.  Without that
 * members of the same build will re-include their dependencies if multiple components rely on a single parent.
 *
 */
	function testInclusionCounterWorksAcrossCalls() {
		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'template.js');
		$result = $this->filter->input('template.js', $content);

		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'double_inclusion.js');
		$result .= $this->filter->input('double_inclusion.js', $content);
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
}
