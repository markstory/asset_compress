<?php

App::import('Lib', 'AssetCompress.AssetFilterCollection');

class AssetFilterCollectionTest extends CakeTestCase {

	function testBuildingFilters() {
		$filters = array('AssetFilter');
		$settings = array(
			'paths' => array()
		);
		$Filters = new AssetFilterCollection($filters, $settings);
		$this->assertTrue($Filters->has('AssetFilter'));
	}

	function testInputOrder() {
		$filters = array('TestFilterOne', 'TestFilterTwo');
		$Filters = new AssetFilterCollection($filters, array());

		$result = $Filters->input('test.js', 'test content');
		$expected = <<<TEXT
FilterTwo::input()
FilterOne::input()
test content
TEXT;
		$this->assertEqual($result, $expected);
	}

	function testOutput() {
		$filters = array('TestFilterOne', 'TestFilterTwo');
		$Filters = new AssetFilterCollection($filters, array());

		$result = $Filters->output('test.js', 'test content');
		$expected = <<<TEXT
FilterTwo::output()
FilterOne::output()
test content
TEXT;
		$this->assertEqual($result, $expected);	
	}
}

class TestFilterOne extends AssetFilter {
	public function input($filename, $contents) {
		return "FilterOne::input()\n" . $contents;
	}
	public function output($build, $content) {
		return "FilterOne::output()\n" . $content;
	}
}

class TestFilterTwo extends AssetFilter {
	public function input($filename, $contents) {
		return "FilterTwo::input()\n" . $contents;
	}
	public function output($build, $content) {
		return "FilterTwo::output()\n" . $content;
	}
}


