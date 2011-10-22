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
		$this->testFilePath = $this->_pluginPath . 'tests/test_files/views/parse/';

		$this->testConfig = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->Task->setConfig($this->config);
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
		$this->assertEqual('addScript', $result[0][2][1]);
	}

	function testParsingSimpleFile() {
		$files = array($this->testFilePath . 'single.ctp');
		$this->Task->setFiles($files);
		$this->Task->_scanFiles();
		$result = $this->Task->_parse();
		$expected = array(
			'addCss' => array(
				'single' => array('one_file'),
				':hash-default' => array('no_build')
			),
			'addScript' => array(
				'single' => array('one_file'),
				':hash-default' => array('no_build')
			)
		);
		$this->assertEqual($expected, $result);
	}

	function testParsingMultipleFile() {
		$files = array($this->testFilePath . 'multiple.ctp');
		$this->Task->setFiles($files);
		$this->Task->_scanFiles();
		$result = $this->Task->_parse();
		$expected = array(
			'addCss' => array(
				'multi' => array('one_file', 'two_file', 'three_file'),
			),
			'addScript' => array(
				'multi' => array('one_file', 'two_file', 'three_file'),
			)
		);
		$this->assertEqual($expected, $result);
	}

	function testParsingArrayFile() {
		$files = array($this->testFilePath . 'array.ctp');
		$this->Task->setFiles($files);
		$this->Task->_scanFiles();
		$result = $this->Task->_parse();

		$expected = array(
			'addCss' => array(
				':hash-default' => array('no', 'build'),
				'array_file' => array('has', 'a_build')
			),
			'addScript' => array(
				':hash-default' => array('no', 'build'),
				'multi_file' => array('one_file', 'two_file')
			)
		);
		$this->assertEqual($expected, $result);
	}
}
