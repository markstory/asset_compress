<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\Filter\ImportInline;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class ImportInlineTest extends TestCase {

	public function setUp() {
		parent::setUp();
		Plugin::load('Red');
		$this->filter = new ImportInline();
		$settings = array(
			'paths' => array(
				APP . 'css/'
			),
			'theme' => 'Red',
		);
		$this->filter->settings($settings);
	}

	public function testReplacement() {
		$content = file_get_contents(APP . 'css' . DS . 'nav.css');
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
		$content = file_get_contents(APP . 'css' . DS . 'has_import.css');
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
