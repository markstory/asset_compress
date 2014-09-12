<?php
namespace AssetCompress\Test\TestCase\Shell\Task;

use AssetCompress\Shell\Task\AssetBuildTask;
use AssetCompress\AssetConfig;
use Cake\Console\Shell;
use Cake\Console\ShellDispatcher;
use Cake\TestSuite\TestCase;

class AssetBuildTaskTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', array(), array(), '', false);

		$this->Task = $this->getMock('AssetCompress\Shell\Task\AssetBuildTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($io)
		);

		$this->testFilePath = APP . 'Template/Parse/';
		$this->testConfig = APP . 'config' . DS . 'config.ini';

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
