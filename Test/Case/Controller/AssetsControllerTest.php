<?php

App::uses('AssetCompressAppController', 'AssetCompress.Controller');
App::uses('AssetsController', 'AssetCompress.Controller');

class TestAssetsController extends AssetsController {
	public function render() {

	}
}

class AssetsControllerTest extends CakeTestCase {

	function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'config' . DS . 'integration.ini';

		$map = array(
			'TEST_FILES/' => $this->_pluginPath . 'Test' . DS . 'test_files' . DS
		);
		AssetConfig::clearAllCachedKeys();

		$config = AssetConfig::buildFromIniFile($this->testConfig, $map);
		$config->filters('js', null, array());
		$this->Controller = new TestAssetsController();
		$this->Controller->constructClasses();
		$this->Controller->response = $this->getMock('CakeResponse');
		$this->Controller->_Config = $config;
		$this->_debug = Configure::read('debug');
	}

	function tearDown() {
		Configure::write('debug', $this->_debug);
	}

	function testDynamicBuildFile() {
		$this->Controller->request->query['file'] = array('library_file.js', 'lots_of_comments.js');

		$this->Controller->response
			->expects($this->once())->method('header')
			->with($this->equalTo('Content-Type: text/javascript'));

		$this->Controller->get('dynamic.js');

		$this->assertPattern('/function test/', $this->Controller->viewVars['contents']);
		$this->assertPattern('/multi line comments/', $this->Controller->viewVars['contents']);
	}

	/**
	 * When debug mode is off, dynamic build files should create errors, this is to try and mitigate
	 * the ability to DOS attack an app, by hammering expensive to generate resources.
     *
     * @expectedException NotFoundException
	 */
	function testDynamicBuildFileDebugOff() {
		Configure::write('debug', 0);
		$this->Controller->request->params['url']['file'] = array('library_file.js', 'lots_of_comments.js');

		$this->Controller->get('dynamic.js');
	}
}
