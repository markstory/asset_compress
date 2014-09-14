<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\Filter\Sprockets;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class SprocketsTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$this->_testFiles = APP;
		$this->_jsDir = $this->_testFiles . 'js' . DS;

		$this->filter = new Sprockets();
		$settings = array(
			'paths' => array(
				$this->_jsDir,
				$this->_jsDir . 'classes' . DS,
			)
		);
		$this->filter->settings($settings);
	}

	public function testInputSimple() {
		$content = file_get_contents($this->_jsDir . 'classes' . DS . 'template.js');

		$result = $this->filter->input('template.js', $content);
		$expected = <<<TEXT
var BaseClass = new Class({

});
var Template = new Class({

});

TEXT;
		$this->assertTextEquals($expected, $result);
	}

	public function testInputWithRecursion() {
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
		$this->assertTextEquals($expected, $result);
	}

	public function testDoubleInclusion() {
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
		$this->assertTextEquals($expected, $result);
	}

	public function testThemeAndPluginInclusion() {
		Plugin::load('TestAsset');
		Plugin::load('Red');

		$settings = array(
			'paths' => array(),
			'theme' => 'Red',
		);
		$this->filter->settings($settings);

		$this->_themeDir = $this->_testFiles . 'Plugin' . DS . $settings['theme'] . DS;

		$content = file_get_contents($this->_themeDir . 'webroot' . DS . 'theme.js');
		$result = $this->filter->input('theme.js', $content);
		$expected = <<<TEXT
var Theme = new Class({

});
var ThemeInclude = new Class({

});

var Plugin = new Class({

});


TEXT;
		$this->assertTextEquals($expected, $result);
	}

/**
 * test that <foo> scans all search paths for a suitable file. Unlike "foo" which only scans the
 * current dir.
 *
 * @return void
 **/
	public function testAngleBracketScanning() {
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
		$this->assertTextEquals($expected, $result);
	}

/**
 * The unique dependency counter should persist across input() calls. Without that
 * members of the same build will re-include their dependencies if multiple components rely on a single parent.
 *
 */
	public function testInclusionCounterWorksAcrossCalls() {
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
		$this->assertTextEquals($expected, $result);
	}
}
