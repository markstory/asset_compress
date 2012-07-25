<?php
App::uses('AssetsController', 'AssetCompress.Controller');
App::uses('CakeResponse', 'Network');
App::uses('AssetConfig', 'AssetCompress.Lib');

class AssetsControllerTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'Config' . DS . 'integration.ini';

		$map = array(
			'TEST_FILES/' => $this->_pluginPath . 'Test' . DS . 'test_files' . DS
		);
		AssetConfig::clearAllCachedKeys();

		$config = AssetConfig::buildFromIniFile($this->testConfig, $map);
		$config->filters('js', null, array());
		$this->Controller = $this->getMock(
			'AssetsController',
			array('render'),
			array(new CakeRequest(null, false), new CakeResponse())
		);
		$this->Controller->constructClasses();
		$this->Controller->response = $this->getMock('CakeResponse');
		$this->Controller->_Config = $config;
		$this->_debug = Configure::read('debug');
	}

	public function tearDown() {
		Configure::write('debug', $this->_debug);
	}

	public function testDynamicBuildFile() {
		$this->Controller->response
			->expects($this->once())->method('type')
			->with($this->equalTo('js'));

		$this->Controller->request->query['file'] = array('library_file.js', 'lots_of_comments.js');
		$this->Controller->get('dynamic.js');

		$this->assertRegExp('/function test/', $this->Controller->viewVars['contents']);
		$this->assertRegExp('/multi line comments/', $this->Controller->viewVars['contents']);
	}

/**
 * When debug mode is off, dynamic build files should create errors, this is to try and mitigate
 * the ability to DOS attack an app, by hammering expensive to generate resources.
 *
 * @expectedException ForbiddenException
 */
	public function testDynamicBuildFileDebugOff() {
		Configure::write('debug', 0);

		$this->Controller->request->query['file'] = array('library_file.js', 'lots_of_comments.js');
		$this->Controller->get('dynamic.js');
	}

}
