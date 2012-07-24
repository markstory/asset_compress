<?php

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('AssetBuildTask', 'AssetCompress.Console/Command/Task');

class AssetBuildTaskTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('AssetBuildTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($out, $out, $in)
		);

		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testFilePath = $this->_pluginPath . 'Test/test_files/View/Parse/';

		$this->testConfig = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'Config' . DS . 'config.ini';
		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->Task->setConfig($this->config);
	}

	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

	public function testScanningSimpleFile() {
		$files = array($this->testFilePath . 'single.ctp');
		$this->Task->setFiles($files);
		$result = $this->Task->scanFiles();

		$this->assertEquals(4, count($result));
		$this->assertEquals('addScript', $result[0][2][1]);
	}

	public function testParsingSimpleFile() {
		$files = array($this->testFilePath . 'single.ctp');
		$this->Task->setFiles($files);
		$this->Task->scanFiles();
		$result = $this->Task->parse();
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
		$this->assertEquals($expected, $result);
	}

	public function testParsingMultipleFile() {
		$files = array($this->testFilePath . 'multiple.ctp');
		$this->Task->setFiles($files);
		$this->Task->scanFiles();
		$result = $this->Task->parse();
		$expected = array(
			'addCss' => array(
				'multi' => array('one_file', 'two_file', 'three_file'),
			),
			'addScript' => array(
				'multi' => array('one_file', 'two_file', 'three_file'),
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testParsingArrayFile() {
		$files = array($this->testFilePath . 'array.ctp');
		$this->Task->setFiles($files);
		$this->Task->scanFiles();
		$result = $this->Task->parse();

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
		$this->assertEquals($expected, $result);
	}
}
