<?php

App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}


$pluginPath = App::pluginPath('AssetCompress');
require_once $pluginPath . 'vendors' . DS . 'shells' . DS . 'tasks' . DS . 'asset_build.php';

Mock::generatePartial(
	'ShellDispatcher', 'TestAssetBuildTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'AssetBuildTask', 'MockAssetBuildTask',
	array('in', 'hr', 'out', 'err', 'createFile', '_stop', '_checkUnitTest')
);

class AssetBuildTest extends CakeTestCase {

	function setUp() {
		parent::setUp();
		$this->Dispatcher =& new TestAssetBuildTaskMockShellDispatcher();
		$this->Task =& new MockAssetBuildTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = App::path('shells');
		
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testFilePath = $this->_pluginPath . 'tests/test_files/views/';
	}

	function tearDown() {
		parent::tearDown();
		unset($this->Dispatcher, $this->Task);
	}

	function testScanningSimpleFile() {
		$files = array($this->testFilePath . 'single.ctp');
		$this->Task->setFiles($files);
		$result = $this->Task->_scanFiles();

		$this->assertEqual(4, count($result));
		$this->assertEqual('script', $result[0][2][1]);
	}
	
	function testParsingSimpleFile() {
		$files = array($this->testFilePath . 'single.ctp');
		$this->Task->setFiles($files);
		$this->Task->_scanFiles();
		$result = $this->Task->_parse();
		$expected = array(
			'css' => array(
				'single' => array('one_file'),
				'' => array('no_build')
			),
			'script' => array(
				'single' => array('one_file'),
				'' => array('no_build')
			)
		);
		debug($result);
		$this->assertEqual($expected, $result);
	}
}