<?php
App::uses('ImportInline', 'AssetCompress.Filter');

class ImportInlineTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = $this->_pluginPath . 'Test/test_files/';

		App::build(array(
			'View' => array($this->_testFiles . 'View' . DS)
		));

		$this->filter = new ImportInline();
		$settings = array(
			'paths' => array(
				$this->_pluginPath . 'Test/test_files/css/'
			),
			'theme' => 'red',
		);
		$this->filter->settings($settings);
	}

	public function testReplacement() {
		$content = file_get_contents($this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS . 'nav.css');
		$result = $this->filter->input('nav.css', $content);
		$expected = <<<TEXT
* {
	margin:0;
	padding:0;
}
#nav {
	width:100%;
}

TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testReplacementNestedAndTheme() {
		$content = file_get_contents($this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS . 'has_import.css');
		$result = $this->filter->input('has_import.css', $content);
		$expected = <<<TEXT
* {
	margin:0;
	padding:0;
}
#nav {
	width:100%;
}

body {
	color: red !important;
}

body {
	color:#f00;
	background:#000;
}

TEXT;
		$this->assertEquals($expected, $result);
	}

}
