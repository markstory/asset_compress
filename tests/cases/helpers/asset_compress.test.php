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
		$this->Helper->Javascript = new JavascriptHelper();
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