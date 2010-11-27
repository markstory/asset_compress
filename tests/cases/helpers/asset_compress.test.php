<?php

App::import('Helper', array('AssetCompress.AssetCompress', 'Html', 'Javascript'));


class AssetCompressHelperTestCase extends CakeTestCase {
/**
 * start a test
 *
 * @return void
 **/
	function startTest() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$testFile = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';

		$this->Helper = new AssetCompressHelper(array('iniFile' => $testFile));
		$this->Helper->Html = new HtmlHelper();
		Router::reload();
		Configure::write('debug', 2);
	}

/**
 * end a test
 *
 * @return void
 **/
	function endTest() {
		unset($this->Helper);
	}

/**
 * test that assets only have one base path attached
 *
 * @return void
 */
	function testIncludeAssets() {
		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'index', 'plugin' => null),
			array('base' => '/some/dir', 'webroot' => '/some/dir/', 'here' => '/some/dir/posts')
		));
		$this->Helper->Html->webroot = '/some/dir/';

		$this->Helper->script('one.js');
		$result = $this->Helper->includeAssets();
		$this->assertPattern('#"/some/dir/asset_compress#', $result, 'double dir set %s');
	}

/**
 * test css addition
 *
 * @return void
 */
	function testCssOrderPreserving() {
		$this->Helper->css('base');
		$this->Helper->css('reset');

		$hash = md5('base_reset');

		$result = $this->Helper->includeAssets();
		$expected = array(
			'link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/css_files/get/' . $hash . '.css?file[]=base&amp;file[]=reset'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * test script addition
 *
 * @return void
 */
	function testScriptOrderPreserving() {
		$this->Helper->script('libraries');
		$this->Helper->script('thing');

		$hash = md5('libraries_thing');

		$result = $this->Helper->includeAssets();
		$expected = array(
			'script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/' . $hash . '.js?file[]=libraries&amp;file[]=thing'
			),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that magic slug builds work.
 *
 * @return void
 */
	function testScriptMagicSlugs() {
		$this->Helper->script('libraries', ':hash-default');
		$this->Helper->script('thing', ':hash-default');
		$this->Helper->script('jquery.js', ':hash-jquery');
		$this->Helper->script('jquery-ui.js', ':hash-jquery');

		$hash1 = md5('libraries_thing');
		$hash2 = md5('jquery.js_jquery-ui.js');

		$result = $this->Helper->includeAssets();
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/' . $hash1 . '.js?file[]=libraries&amp;file[]=thing'
			)),
			'/script',
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/' . $hash2 . '.js?file[]=jquery.js&amp;file[]=jquery-ui.js'
			)),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test generating two script files.
 *
 * @return void
 */
	function testMultipleScriptFiles() {
		$this->Helper->script('libraries', 'default');
		$this->Helper->script('thing', 'second');

		$result = $this->Helper->includeAssets();
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/default.js?file[]=libraries'
			)),
			'/script',
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/second.js?file[]=thing'
			)),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test includeJs() with multiple destination files.
 *
 * @return void
 */
	function testIncludeJsMultipleDestination() {
		$this->Helper->script('libraries', 'default');
		$this->Helper->script('thing', 'second');
		$this->Helper->script('other', 'third');

		$result = $this->Helper->includeJs('second', 'default');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/second.js?file[]=thing'
			)),
			'/script',
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/default.js?file[]=libraries'
			)),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test includeCss() with multiple destination files.
 *
 * @return void
 */
	function testIncludeCssMultipleDestination() {
		$this->Helper->css('libraries', 'default');
		$this->Helper->css('thing', 'second');
		$this->Helper->css('other', 'third');

		$result = $this->Helper->includeCss('second', 'default');
		$expected = array(
			array('link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/css_files/get/second.css?file[]=thing'
			)),
			array('link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/css_files/get/default.css?file[]=libraries'
			)),
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that including assets removes them from the list of files to be included.
 *
 * @return void
 */
	function testIncludingFilesRemovesFromQueue() {
		$this->Helper->css('libraries', 'default');
		$result = $this->Helper->includeCss('default');
		$expected = array(
			'link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/css_files/get/default.css?file[]=libraries'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Helper->includeCss('default');
		$this->assertEqual($result, '');
	}

/**
 * test timestamping assets.
 *
 * @return void
 */
	function testTimestampping() {
		Configure::write('debug', 1);
		$this->Helper->script('libraries', 'default');
		$result = $this->Helper->includeAssets();
		$this->assertPattern('/default\.\d+\.js/', $result);

		Configure::write('debug', 2);
	}

/**
 * test configuration
 *
 * @return void
 */
	function testConfig() {
		$result = $this->Helper->config('Css.stripComments');
		$this->assertTrue($result, 'Reading is busted');

		$this->assertNull($this->Helper->config('Garbage.pail'));

		$this->Helper->config('Css.stripComments', false);
		$result = $this->Helper->config('Css.stripComments');
		$this->assertFalse($result, 'writing is busted');
	}

/**
 * test that a baseurl configuration works well.
 *
 * @return void
 */
	function testBaseUrl() {
		$this->Helper->config('General.baseUrl', 'http://cdn.example.com');
		$this->Helper->script('jquery', 'default');
		$result = $this->Helper->includeJs();
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => 'http://cdn.example.com/asset_compress/js_files/get/default.js'
			))
		);
		$this->assertTags($result, $expected);
	}
}