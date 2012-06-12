<?php
App::import('Lib', 'AssetCompress.filter/ScssFilter');

class ScssFilterTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_cssDir = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'css' . DS;

		$this->filter = new ScssFilter();
	}

	function testParsing() {
		$content = file_get_contents($this->_cssDir . DS . 'test.scss');

		$result = $this->filter->input($this->_cssDir . DS . 'test.scss', $content);
		
		$expected = file_get_contents($this->_cssDir . DS . 'compiled_scss.css');
		
		$this->assertEqual($result, $expected);
	 }

}