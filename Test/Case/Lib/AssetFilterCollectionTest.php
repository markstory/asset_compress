<?php
App::uses('AssetFilter', 'AssetCompress.Lib');
App::uses('AssetFilterCollection', 'AssetCompress.Lib');

class AssetFilterCollectionTest extends CakeTestCase {

	public function testBuildingFilters() {
		$filters = array('AssetFilter');
		$settings = array(
			'paths' => array()
		);
		$Filters = new AssetFilterCollection($filters, $settings, array());
		$this->assertTrue($Filters->has('AssetFilter'));
		$this->assertFalse($Filters->has('Boogers'));
	}

	public function testFilterSettings() {
		$filters = array('TestFilterOne', 'TestFilterTwo');
		$settings = array(
			'TestFilterOne' => array(
				'key' => 'value'
			)
		);
		$Filters = new AssetFilterCollection($filters, array(), $settings);
		$result = $Filters->get('TestFilterOne');
		$this->assertEquals(array('key' => 'value'), $result->settings);
	}

	public function testInputOrder() {
		$filters = array('TestFilterOne', 'TestFilterTwo');
		$Filters = new AssetFilterCollection($filters, array(), array());

		$result = $Filters->input('test.js', 'test content');
		$expected = <<<TEXT
FilterTwo::input()
FilterOne::input()
test content
TEXT;
		$this->assertTextEquals($expected, $result);
	}

	public function testOutput() {
		$filters = array('TestFilterOne', 'TestFilterTwo');
		$Filters = new AssetFilterCollection($filters, array(), array());

		$result = $Filters->output('test.js', 'test content');
		$expected = <<<TEXT
FilterTwo::output()
FilterOne::output()
test content
TEXT;
		$this->assertTextEquals($expected, $result);
	}
}

class TestFilterOne extends AssetFilter {
	public function settings($settings) {
		$this->settings = $settings;
	}
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


