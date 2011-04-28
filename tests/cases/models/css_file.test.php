<?php
App::import('Model', 'AssetCompress.CssFile');
App::import('Model', 'AssetCompress.AssetFilterInterface');
App::import('Model', 'AssetCompress.AssetProcessorInterface');

class TestProcessor implements AssetProcessorInterface {
	public function process($fileName, $content) {
		return "/* Begin of file */\n" . $content;
	}
}

class WrongInterfaceProcessor implements AssetFilterInterface {
	public function filter($content) {
		return "/* Begin of file */\n" . $content;
	}
}

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
		$restore = App::path('views');
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
		
		App::build(array('views' => $restore));
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

/**
 * test pre-processing
 *
 * @return void
 **/
	function testPreProcessor() {
		$this->CssFile->settings['stripComments'] = false;
		$this->CssFile->settings['processors'] = array('Test');
		$this->CssFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests/test_files/css/',
		);
		$result = $this->CssFile->process('has_import');
		$expected = <<<TEXT
/* Begin of file */
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
 * test pre-processing with filter
 *
 * @return void
 **/
	function testPreProcessorWithFilter() {
		$this->CssFile->settings['stripComments'] = false;
		$this->CssFile->settings['filters'] = array('CssStripComments');
		$this->CssFile->settings['processors'] = array('Test');
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
 * test invalid pre-processor
 *
 * @return void
 **/
	function testInvalidPreProcessors() {
		$this->CssFile->settings['stripComments'] = false;
		$this->CssFile->settings['processors'] = array('InvalidTest');
		$this->CssFile->settings['searchPaths'] = array(
			$this->_pluginPath . 'tests/test_files/css/',
		);

		try {
			$this->CssFile->process('has_import');
		} catch (Exception $e) {
			$message = $e->getMessage();
		}

		$this->assertEqual($message, 'Cannot not load InvalidTestProcessor.');

		$this->CssFile->settings['processors'] = array('WrongInterface');

		try {
			$this->CssFile->process('has_import');
		} catch (Exception $e) {
			$message = $e->getMessage();
		}

		$this->assertEqual($message, 'Cannot use processors that do not implement AssetProcessorInterface');
	}
}