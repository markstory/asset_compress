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
		
		$result = $this->Helper->includeAssets();
		$expected = array(
			'link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/css_files/get/default.css?file[]=base&file[]=reset'
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

		$result = $this->Helper->includeAssets();
		$expected = array(
			'script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/js_files/get/default.js?file[]=libraries&file[]=thing'
			),
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

}