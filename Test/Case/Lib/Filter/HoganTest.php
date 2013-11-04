<?php
App::uses('Hogan', 'AssetCompress.Filter');

class HoganTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_path = $this->_pluginPath . 'Test/test_files/hogan/';

		$this->filter = new Hogan();
		$settings = array(
			'node' => trim(`which node`),
			'node_path' => getenv('NODE_PATH')
		);
		$this->filter->settings($settings);

		$hasHogan = `which hulk`;
		$this->skipIf(empty($hasHogan), 'Nodejs and Hogan.js to be installed');
	}

	public function testInput() {
		$content = file_get_contents($this->_path . 'test.mustache');
		$result = $this->filter->input($this->_path . 'test.mustache', $content);
		$this->assertContains('window.JST["test"] = ', $result, 'Missing window.JST');
		$this->assertContains('function(c,p,i)', $result, 'Missing hogan output');
	}

}
