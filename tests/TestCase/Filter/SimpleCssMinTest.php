<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\Filter\SimpleCssMin;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class SimpleCssMinTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$this->_cssDir = APP . 'css' . DS;
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
