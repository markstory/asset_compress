<?php
namespace AssetCompress\Test\TestCase\Shell;

use AssetCompress\Shell\AssetCompressShell;
use AssetCompress\AssetConfig;
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

		$this->Shell = new AssetCompressShell($io);
		$this->Shell->initialize();

		$this->testConfig = APP . 'config' . DS . 'config.ini';

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->Shell->setConfig($this->config);
		$this->Shell->AssetBuild->setConfig($this->config);
	}

/**
 * Teardown method.
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Shell);
	}

/**
 * Test building files from the config file.
 *
 * @return void
 */
	public function testBuildFiles() {
		$this->Shell->build();
	}

/**
 * Test building files from the config file.
 *
 * @return void
 */
	public function testBuildFilesWithTheme() {
		$this->markTestIncomplete('Not done');
	}

}
