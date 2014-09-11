<?php
App::uses('SimpleCssMin', 'AssetCompress.Filter');

class SimpleCssMinTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_cssDir = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS;
		$this->filter = new SimpleCssMin();
	}

	public function testUnminified() {
		$content = file_get_contents($this->_cssDir . 'unminified.css');
		$result = $this->filter->output($this->_cssDir . 'unminified.css', $content);
		$expected = file_get_contents($this->_cssDir . 'minified.css');
		$this->assertEquals($expected, $result);
	}

	public function testAlreadyMinified() {
		$content = file_get_contents($this->_cssDir . 'minified.css');
		$result = $this->filter->output($this->_cssDir . 'minified.css', $content);
		$expected = file_get_contents($this->_cssDir . 'minified.css');
		$this->assertEquals($expected, $result);
	}

}
