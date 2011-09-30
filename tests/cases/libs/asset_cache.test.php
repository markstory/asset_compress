<?php
App::import('Lib', 'AssetCompress.AssetCache');
App::import('Lib', 'AssetCompress.AssetConfig');

class AssetCacheTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'integration.ini';

		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->config->cachePath('js', TMP);
		$this->config->set('js.timestamp', true);
		$this->cache = new AssetCache($this->config);
	}

	function testWrite() {
		$this->config->set('js.timestamp', false);
		$result = $this->cache->write('test.js', 'Some content');
		$this->assertTrue($result);
		$contents = file_get_contents(TMP . 'test.js');
		$this->assertEqual('Some content', $contents);
		unlink(TMP . 'test.js');
	}

	function testWriteTimestamp() {
		$this->assertTrue($this->config->get('js.timestamp'));

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

	function testIsFreshNoBuild(){
		$this->assertFalse($this->cache->isFresh('libs.js'));
	}

	function testIsFreshSuccess() {
		touch(TMP . '/libs.js');

		$this->assertTrue($this->cache->isFresh('libs.js'));
		unlink(TMP . '/libs.js');
	}

	function testIsFreshFailure() {
		// touch the build and component file.
		// this triggers stale smells.
		touch(TMP . '/libs.js');
		touch($this->_pluginPath . 'tests/test_files/js/classes/base_class.js');

		$this->assertFalse($this->cache->isFresh('libs.js'));
		unlink(TMP . '/libs.js');
	}
	
}
