<?php
App::uses('AssetCache', 'AssetCompress.Lib');
App::uses('AssetConfig', 'AssetCompress.Lib');

class AssetCacheTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';

		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->config->cachePath('js', TMP);
		$this->cache = new AssetCache($this->config);
	}

	function testWrite() {
		$this->config->set('js.timestamp', false);
		$result = $this->cache->write('test.js', 'Some content');
		$this->assertNotEqual($result, false);
		$contents = file_get_contents(TMP . 'test.js');
		$this->assertEqual('Some content', $contents);
		unlink(TMP . 'test.js');
	}

	function testWriteTimestamp() {
		$this->assertEqual('1', $this->config->get('js.timestamp'));

		$now = time();
		$this->cache->write('test.js', 'Some content');

		$contents = file_get_contents(TMP . 'test.v' . $now . '.js');
		$this->assertEqual('Some content', $contents);
		unlink(TMP . 'test.v' . $now . '.js');
	}


	function testWriteTimestampUsingTimestampFile() {
		$this->config->set('General.timestampFile', true);
		$time = 1235;
		$this->config->writeTimestampFile($time);

		$this->cache->write('test.js', 'Timestamp file.');
		$contents = file_get_contents(TMP . 'test.v' . $time . '.js');
		$this->assertEqual('Timestamp file.', $contents);
	}
}
