<?php
App::import('Lib', 'AssetCompress.AssetCache');
App::import('Lib', 'AssetCompress.AssetConfig');

class AssetCacheTest extends CakeTestCase {

	function setUp() {
		parent::setUp();
		AssetConfig::clearBuildTimestamp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = App::pluginPath('AssetCompress') . 'tests' . DS . 'test_files' . DS;
		$this->testConfig = $this->_testFiles . 'config' . DS . 'integration.ini';
		$this->_themeConfig = $this->_testFiles . 'config' . DS . 'themed.ini';

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
		$this->cache->setTimestamp('test.js', $now);
		$this->cache->write('test.js', 'Some content');

		$contents = file_get_contents(TMP . 'test.v' . $now . '.js');
		$this->assertEqual('Some content', $contents);
		unlink(TMP . 'test.v' . $now . '.js');
	}

	function testIsFreshNoBuild(){
		$this->assertFalse($this->cache->isFresh('libs.js'));
	}

	function testIsFreshSuccess() {
		$this->config->set('js.timestamp', false);
		touch(TMP . '/libs.js');

		$this->assertTrue($this->cache->isFresh('libs.js'));
		unlink(TMP . '/libs.js');
	}

	function testThemeFileSaving() {
		$this->config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$this->config->theme('blue');
		$this->config->cachePath('css', TMP);
		$this->cache = new AssetCache($this->config);

		$this->cache->write('themed.css', 'theme file.');
		$contents = file_get_contents(TMP . 'blue-themed.css');
		$this->assertEqual('theme file.', $contents);
	}

	function testGetSetTimestamp() {
		$time = time();
		$this->cache->setTimestamp('libs.js', $time);
		$result = $this->cache->getTimestamp('libs.js');
		$this->assertEqual($time, $result);

		$result = $this->cache->getTimestamp('foo.bar.js');
		$this->assertEqual($time, $result);

		$this->config->set('js.timestamp', false);
		$result = $this->cache->getTimestamp('foo.bar.js');
		$this->assertFalse($result);
	}

	function testBuildFileNameTheme() {
		$this->config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$this->config->theme('blue');
		$this->config->cachePath('css', TMP);
		$this->cache = new AssetCache($this->config);

		$result = $this->cache->buildFileName('themed.css');
		$this->assertEqual('blue-themed.css', $result);
	}

	function testBuildFileNameTimestampNoValue() {
		$this->config->cachePath('js', TMP);
		$this->cache = new AssetCache($this->config);

		$time = time();
		$result = $this->cache->buildFileName('libs.js');
		$this->assertEqual('libs.v' . $time . '.js', $result);
	}

	function testTimestampFromCache() {
		$this->config->general('cacheConfig', true);
		$this->config->set('js.timestamp', true);

		$time = time();
		$this->cache->buildFilename('libs.js');

		// delete the file so we know we hit the cache.
		unlink(TMP . AssetConfig::BUILD_TIME_FILE);

		$result = $this->cache->buildFilename('libs.js');
		$this->assertEqual('libs.v' . $time . '.js', $result);
	}

}
