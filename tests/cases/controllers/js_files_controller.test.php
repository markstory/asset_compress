<?php

App::import('Controller', 'AssetCompress.JsFiles');
App::import('Model', 'AssetCompress.JsFile');

Mock::generatePartial('JsFilesController', 'JsFilesControllerMock', array('render', 'redirect', '_stop', 'header'));
Mock::generate('JsFile', 'JsFileMock');

class JsFilesControllerTestCase extends CakeTestCase {
/**
 * start a test
 *
 * @return void
 **/
	function startTest() {
		$this->JsFiles = new JsFilesControllerMock();
		$this->JsFiles->plugin = 'AssetCompress';
		$this->JsFiles->JsFile = new JsFileMock();
		$this->_pluginPath = $this->_findPlugin();
	}
/**
 * find the asset_compress path
 *
 * @return void
 **/
	function _findPlugin() {
		$paths = Configure::read('pluginPaths');
		foreach ($paths as $path) {
			if (is_dir($path . 'asset_compress')) {
				return $path . 'asset_compress' . DS;
			}
		}
		throw new Exception('Could not find my directory, bailing hard!');
	}
/**
 * endTest
 *
 * @return void
 **/
	function endTest() {
		unset($this->JsFiles);
		ClassRegistry::flush();
	}
/**
 * test join
 *
 * @return void
 **/
	function testJoin() {
		Configure::write('Cache.disable', true);
		$this->JsFiles->expectOnce('header', array('Content-Type', 'text/javascript'));
		$this->JsFiles->JsFile->expectOnce('process', array(array('One', 'Two')));
		$this->JsFiles->JsFile->setReturnValue('process', 'Im glued together');
		$this->JsFiles->expectOnce('render', array('contents'));
		$this->JsFiles->join('One', 'Two');

		$this->assertEqual($this->JsFiles->layout, 'script');
		$this->assertEqual($this->JsFiles->viewVars['contents'], 'Im glued together');
	}
}