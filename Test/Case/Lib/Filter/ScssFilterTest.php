<?php
App::uses('ScssFilter', 'AssetCompress.Filter');

class ScssFilterTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_cssDir = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS;

		$this->filter = new ScssFilter();
	}

	function testParsing() {
		$content = file_get_contents($this->_cssDir . 'test.scss');

		$result = $this->filter->input($this->_cssDir . 'test.scss', $content);
		
		$expected = file_get_contents($this->_cssDir . 'compiled_scss.css');
		
		$this->assertEquals($expected, $result);
	 }

}