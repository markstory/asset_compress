<?php
App::uses('AssetCache', 'AssetCompress.Lib');
App::uses('AssetConfig', 'AssetCompress.Lib');

class AssetCacheTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		AssetConfig::clearBuildTimestamp();
		$this->_testFiles = App::pluginPath('AssetCompress') . 'Test' . DS . 'test_files' . DS;
		$this->testConfig = $this->_testFiles . 'Config' . DS . 'integration.ini';
		$this->_themeConfig = $this->_testFiles . 'Config' . DS . 'themed.ini';

		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->config->cachePath('js', TMP);
		$this->config->set('js.timestamp', true);
		$this->cache = new AssetCache($this->config);
	}

	public function testWrite() {
		$this->config->set('js.timestamp', false);
		$result = $this->cache->write('test.js', 'Some content');
		$this->assertNotEqual($result, false);
		$contents = file_get_contents(TMP . 'test.js');
		$this->assertEquals('Some content', $contents);
		unlink(TMP . 'test.js');
	}

	public function testWriteTimestamp() {
		$this->assertTrue($this->config->get('js.timestamp'));

		$now = time();
		$this->cache->setTimestamp('test.js', $now);
		$this->cache->write('test.js', 'Some content');

		$contents = file_get_contents(TMP . 'test.v' . $now . '.js');
		$this->assertEquals('Some content', $contents);
		unlink(TMP . 'test.v' . $now . '.js');
	}

	public function testIsFreshNoBuild() {
		$this->assertFalse($this->cache->isFresh('libs.js'));
	}

	public function testIsFreshSuccess() {
		$this->config->set('js.timestamp', false);
		touch(TMP . '/libs.js');

		$this->assertTrue($this->cache->isFresh('libs.js'));
		unlink(TMP . '/libs.js');
	}

	public function testThemeFileSaving() {
		$this->config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$this->config->theme('blue');
		$this->config->cachePath('css', TMP);
		$this->cache = new AssetCache($this->config);

		$this->cache->write('themed.css', 'theme file.');
		$contents = file_get_contents(TMP . 'blue-themed.css');
		$this->assertEquals('theme file.', $contents);
	}

	public function testGetSetTimestamp() {
		$time = time();
		$this->cache->setTimestamp('libs.js', $time);
		$result = $this->cache->getTimestamp('libs.js');
		$this->assertEquals($time, $result);

		$result = $this->cache->getTimestamp('foo.bar.js');
		$this->assertEquals($time, $result);

		$this->config->set('js.timestamp', false);
		$result = $this->cache->getTimestamp('foo.bar.js');
		$this->assertFalse($result);
	}

	public function testBuildFileNameTheme() {
		$this->config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$this->config->theme('blue');
		$this->config->cachePath('css', TMP);
		$this->cache = new AssetCache($this->config);

		$result = $this->cache->buildFileName('themed.css');
		$this->assertEquals('blue-themed.css', $result);
	}

	public function testBuildFileNameTimestampNoValue() {
		$this->config->cachePath('js', TMP);
		$this->cache = new AssetCache($this->config);

		$time = time();
		$result = $this->cache->buildFileName('libs.js');
		$this->assertEquals('libs.v' . $time . '.js', $result);
	}

	public function testTimestampFromCache() {
		$this->config->general('cacheConfig', true);
		$this->config->set('js.timestamp', true);

		$time = time();
		$this->cache->buildFilename('libs.js');

		// delete the file so we know we hit the cache.
		unlink(TMP . AssetConfig::BUILD_TIME_FILE);

		$result = $this->cache->buildFilename('libs.js');
		$this->assertEquals('libs.v' . $time . '.js', $result);
	}

}
