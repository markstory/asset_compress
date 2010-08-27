<?php
App::import('Model', 'AssetCompress.CssFile');

class CssFileTestCase extends CakeTestCase {
/**
 * startTest
 *
 * @return void
 **/
	function startTest() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$testFile = $this->_pluginPath . 'tests/test_files/config/config.ini';
		$this->CssFile = new CssFile($testFile);
	}
/**
 * test the constuction and ini reading.
 *
 * @return void
 **/
	function testConstruction() {
		$testFile = $this->_pluginPath . 'tests/test_files/config/config.ini';
		$CssFile = new CssFile($testFile);
		$this->assertTrue($CssFile->settings['stripComments']);
		$this->assertEqual($CssFile->settings['searchPaths'], array('/test/css', '/test/css/more'));
	}
/**
 * test @import processing
 *
 * @return void
 **/
	function testImportProcessing() {
		$this->CssFile->settings['stripComments'] = false;
		$this->CssFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests/test_files/css/',
		);
		$result = $this->CssFile->process('has_import');
		$expected = <<<TEXT
* {
	margin:0;
	padding:0;
}
#nav {
	width:100%;
}
body {
	color:#f00;
	background:#000;
}
TEXT;
		$this->assertEqual($result, $expected);
	}
/**
 * test removal of comment blocks.
 *
 * @return void
 **/
	function testCommentRemoval() {
		$this->CssFile->settings['stripComments'] = true;
		$this->CssFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests/test_files/css/',
		);
		$result = $this->CssFile->process('has_comments');
		$expected = <<<TEXT
body {
	color:#000;
}
#match-timeline {
	clear:both;
	padding-top:10px;
}
TEXT;
		$this->assertEqual($result, $expected);
	}

/**
 * test that files are written to the cache and include file headers.
 *
 * @return void
 */
	function testCachingAndFileHeaders() {
		$this->CssFile->settings['stripComments'] = true;
		$this->CssFile->settings['cacheFiles'] = true;
		$this->CssFile->settings['cacheFilePath'] = TMP . 'tests' . DS;
		$this->CssFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests/test_files/css/',
		);
		$contents = $this->CssFile->process('has_comments');
		$result = $this->CssFile->cache('test_css_asset', $contents);
		$this->assertTrue($result);


		$time = time();
		$expected = <<<TEXT
/* asset_compress $time */
body {
	color:#000;
}
#match-timeline {
	clear:both;
	padding-top:10px;
}
TEXT;
		$contents = file_get_contents(TMP . 'tests/test_css_asset');
		$this->assertEqual($contents, $expected);
		unlink(TMP . 'tests/test_css_asset');
	}

/**
 * test using addTheme() to set the theme.
 *
 * @return void
 */
	function testSettingTheme() {
		$this->CssFile->settings['searchPaths'] = array(
			'WEBROOT/something/',
			'WEBROOT/something/else/'
		);
		$this->CssFile->addTheme('test_theme');

		$expected = array(
			'APP/views/themed/test_theme/webroot/something/',
			'APP/views/themed/test_theme/webroot/something/else/',
			'WEBROOT/something/',
			'WEBROOT/something/else/'
		);
		$this->assertEqual($this->CssFile->settings['searchPaths'], $expected);
	}

/**
 * test using addTheme() with non app dir themes
 *
 * @return void
 */
	function testSettingThemeWithAlternatePaths() {
		$alternatePath = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS;
		App::build(array(
			'views' => array($alternatePath)
		));
		$this->CssFile->settings['searchPaths'] = array(
			'WEBROOT/something/',
			'WEBROOT/something/else/'
		);
		$this->CssFile->addTheme('test_theme');

		$expected = array(
			$alternatePath . 'themed/test_theme/webroot/something/',
			$alternatePath . 'themed/test_theme/webroot/something/else/',
			'WEBROOT/something/',
			'WEBROOT/something/else/'
		);
		$this->assertEqual($this->CssFile->settings['searchPaths'], $expected);
		
		App::build();
	}

/**
 * test extension detection
 *
 * @return void
 */
	function testValidExtension() {
		$filename = 'avatar.png';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'default.css';
		$this->assertTrue($this->CssFile->validExtension($filename));

		$filename = 'default.1282920894.css';
		$this->assertTrue($this->CssFile->validExtension($filename));

		$filename = 'bl.1282920894.jpg';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'default.js';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'default.1282920894.js';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'readme.txt';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'readme.md';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'README.MD';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'my.my.my.Xlsx';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'mysoopersecretpassword';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'ÒÓÔÕŌŎǑŐƠØǾ.txt';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = 'военных.js';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = '除此之外.js';
		$this->assertFalse($this->CssFile->validExtension($filename));

		$filename = '除此之外.css';
		$this->assertTrue($this->CssFile->validExtension($filename));
	}

}