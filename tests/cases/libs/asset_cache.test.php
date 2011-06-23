<?php
App::import('Lib', 'AssetCompress.AssetCache');
App::import('Lib', 'AssetCompress.AssetConfig');

class AssetCacheTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->testConfig = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';
		
		AssetConfig::clearAllApcKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->config->cachePath('js', TMP);
		$this->cache = new AssetCache($this->config);
	}

	function testWrite() {
		$this->config->set('js.timestamp', false);
		$result = $this->cache->write('test.js', 'Some content');
		$this->assertTrue($result);
		$contents = file_get_contents(TMP . 'test.js');
		$this->assertEqual('Some content', $contents);
	}

	function testWriteTimestamp() {
		$this->assertTrue($this->config->get('js.timestamp'));

		$now = time();
		$this->cache->write('test.js', 'Some content');

		$contents = file_get_contents(TMP . 'test.v' . $now . '.js');
		$this->assertEqual('Some content', $contents);
	}
}
