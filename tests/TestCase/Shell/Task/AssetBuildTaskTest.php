<?php
namespace AssetCompress\Test\TestCase\Shell\Task;

use AssetCompress\Shell\Task\AssetBuildTask;
use AssetCompress\AssetConfig;
use Cake\Console\Shell;
use Cake\Console\ShellDispatcher;
use Cake\TestSuite\TestCase;

/**
 * AssetBuildTask test case.
 */
class AssetBuildTaskTest extends TestCase {

/**
 * setup method.
 *
 * @return void
 */
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

/**
 * Teardown method.
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * Test building files from the config file.
 *
 * @return void
 */
	public function testBuildFiles() {
		$this->markTestIncomplete('Not done');
	}

}
