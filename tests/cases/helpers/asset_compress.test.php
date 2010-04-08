<?php

App::import('Helper', array('AssetCompress.AssetCompress', 'Html', 'Javascript'));


class AssetCompressHelperTestCase extends CakeTestCase {
/**
 * start a test
 *
 * @return void
 **/
	function startTest() {
		$this->Helper = new AssetCompressHelper();
		$this->Helper->Html = new HtmlHelper();
		Router::reload();
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
 * end a test
 *
 * @return void
 **/
	function endTest() {
		unset($this->Helper);
	}
}