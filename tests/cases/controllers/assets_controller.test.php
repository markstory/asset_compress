<?php

App::import('Controller', 'AssetCompress.Assets');

class TestAssetsController extends AssetsController {
	public function header() {
		$this->headers[] = func_get_args();
	}
	public function render() {

	}
}

class AssetsControllerTest extends CakeTestCase {

	function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'integration.ini';

		$map = array(
			'TEST_FILES/' => $this->_pluginPath . 'tests' . DS . 'test_files' . DS
		);
		AssetConfig::clearAllCachedKeys();

		$config = AssetConfig::buildFromIniFile($this->testConfig, $map);
		$config->filters('js', null, array());
		$this->Controller = new TestAssetsController();
		$this->Controller->constructClasses();
		$this->Controller->_Config = $config;
		$this->_debug = Configure::read('debug');
	}

	function tearDown() {
		Configure::write('debug', $this->_debug);
	}

	function testDynamicBuildFile() {
		$this->Controller->params['url']['file'] = array('library_file.js', 'lots_of_comments.js');

		$this->Controller->get('dynamic.js');
		$this->assertEqual(1, count($this->Controller->headers[0]));
		$this->assertEqual('Content-Type: text/javascript', $this->Controller->headers[0][0]);
		$this->assertPattern('/function test/', $this->Controller->viewVars['contents']);
		$this->assertPattern('/multi line comments/', $this->Controller->viewVars['contents']);
	}

	/**
	 * When debug mode is off, dynamic build files should create errors, this is to try and mitigate
	 * the ability to DOS attack an app, by hammering expensive to generate resources.
	 */
	function testDynamicBuildFileDebugOff() {
		Configure::write('debug', 0);
		$this->Controller->params['url']['file'] = array('library_file.js', 'lots_of_comments.js');

		$this->Controller->get('dynamic.js');
		$this->assertEqual(1, count($this->Controller->headers[0]));
		$this->assertEqual('HTTP/1.1 404 Not Found', $this->Controller->headers[0][0]);
		$this->assertFalse(isset($this->Controller->viewVars['contents']));
	}
}
