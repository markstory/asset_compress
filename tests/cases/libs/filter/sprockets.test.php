<?php
App::import('Lib', 'AssetCompress.filter/Sprockets');

class SprocketsTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');

		$this->filter = new Sprockets();
		$settings = array(
			'paths' => array(
				$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
				$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS,
			)
		);
		$this->filter->settings($settings);
	}

	function testInputSimple() {
		$content = file_get_contents($this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS . 'template.js');

		$result = $this->filter->input('template.js', $content);
		$expected = <<<TEXT
var BaseClass = new Class({

});
var Template = new Class({

});
TEXT;
		$this->assertEqual($result, $expected);

		$content = file_get_contents($this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . 'classes' . DS . 'nested_class.js');
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

}
