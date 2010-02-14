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
		$this->_pluginPath = App::pluginPath('AssetCompress');
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
		$this->JsFiles->expectOnce('header', array('Content-Type: text/javascript'));
		$this->JsFiles->JsFile->expectOnce('process', array(array('One', 'Two')));
		$this->JsFiles->JsFile->setReturnValue('process', 'Im glued together');
		$this->JsFiles->expectOnce('render', array('contents'));
		$this->JsFiles->params['url']['file'] = array('One', 'Two');
		$this->JsFiles->get('default');

		$this->assertEqual($this->JsFiles->layout, 'script');
		$this->assertEqual($this->JsFiles->viewVars['contents'], 'Im glued together');
	}
}