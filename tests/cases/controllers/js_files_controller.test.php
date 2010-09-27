<?php

App::import('Controller', 'AssetCompress.JsFiles');
App::import('Model', 'AssetCompress.JsFile');

Mock::generatePartial(
	'JsFilesController', 'JsFilesControllerMock', array('render', 'redirect', '_stop', 'header', 'cakeError')
);
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

		$this->_cache = Configure::read('Cache');
		Configure::write('Cache.disable', true);
	}
/**
 * endTest
 *
 * @return void
 **/
	function endTest() {
		unset($this->JsFiles);
		ClassRegistry::flush();
		Configure::write('Cache', $this->_cache);
	}
/**
 * test join
 *
 * @return void
 **/
	function testJoin() {
		$this->JsFiles->expectOnce('header', array('Content-Type: text/javascript'));
		$this->JsFiles->JsFile->expectOnce('process', array(array('One', 'Two')));
		$this->JsFiles->JsFile->setReturnValue('process', 'Im glued together');
		$this->JsFiles->expectOnce('render', array('contents'));

		$this->JsFiles->params['pass'] = array('default.js');
		$this->JsFiles->params['url']['file'] = array('One', 'Two');
		$this->JsFiles->get('default');

		$this->assertEqual($this->JsFiles->layout, 'script');
		$this->assertEqual($this->JsFiles->viewVars['contents'], 'Im glued together');
	}

/**
 * test that greedy parse extensions works.
 *
 * @return void
 */
	function testGreedyParseExtensions() {
		$this->JsFiles->expectOnce('cakeError', array('error404'));

		$this->JsFiles->params['pass'] = array('default');
		$this->JsFiles->params['url']['file'] = array('One', 'Two');
		$this->JsFiles->params['url']['ext'] = 'jpg';
		$this->JsFiles->get('default');
	}
}