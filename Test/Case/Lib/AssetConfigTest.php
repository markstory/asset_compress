<?php
App::uses('AssetConfig', 'AssetCompress.Lib');

class AssetConfigTest extends CakeTestCase {

	public function setUp() {
		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'engine' => 'File'
		));

		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = App::pluginPath('AssetCompress') . 'Test' . DS . 'test_files' . DS;
		$this->testConfig = $this->_testFiles . 'Config' . DS . 'config.ini';
		$this->_themeConfig = $this->_testFiles . 'Config' . DS . 'themed.ini';

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
	}

	public function testBuildFromIniFile() {
		$config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->assertEquals(1, $config->get('js.timestamp'));
		$this->assertEquals(1, $config->general('writeCache'));
	}

	public function testExceptionOnBogusFile() {
		try {
			$config = AssetConfig::buildFromIniFile('/bogus');
			$this->assertFalse(true, 'Exception not thrown.');
		} catch (Exception $e) {
			$this->assertEquals('Configuration file "/bogus" was not found.', $e->getMessage());
		}
	}

	public function testFilters() {
		$result = $this->config->filters('js');
		$this->assertEquals(array('sprockets', 'jsyuicompressor'), $result);

		$result = $this->config->filters('js', 'libs.js');
		$this->assertEquals(array('sprockets', 'jsyuicompressor', 'uglify'), $result);

		$this->assertEquals(array(), $this->config->filters('nothing'));
	}

	public function testSettingFilters() {
		$this->config->filters('js', null, array('uglify'));
		$this->assertEquals(array('uglify'), $this->config->filters('js'));
		$this->assertEquals(array('uglify'), $this->config->filters('js', 'libs.js'));

		$this->config->filters('js', 'libs.js', array('sprockets'));
		$this->assertEquals(array('uglify'), $this->config->filters('js'));
		$this->assertEquals(array('uglify', 'sprockets'), $this->config->filters('js', 'libs.js'));
	}

	public function testFiles() {
		$result = $this->config->files('libs.js');
		$expected = array('jquery.js', 'mootools.js', 'class.js');
		$this->assertEquals($expected, $result);

		$result = $this->config->files('foo.bar.js');
		$expected = array('test.js');
		$this->assertEquals($expected, $result);

		$this->assertEquals(array(), $this->config->files('nothing here'));
	}

	public function testSettingFiles() {
		$this->config->files('new_build.js', array('one.js', 'two.js'));

		$this->assertEquals(array('one.js', 'two.js'), $this->config->files('new_build.js'));
	}

	public function testPathConstantReplacement() {
		$result = $this->config->paths('css');
		$result = str_replace('/', DS, $result);
		$this->assertEquals(array(WWW_ROOT . 'css' . DS), $result);
		$this->assertEquals(array(), $this->config->paths('nothing'));
	}

	public function testPaths() {
		$this->config->paths('js', array('/path/to/files', 'WEBROOT/js'));
		$result = $this->config->paths('js');
		$result = str_replace('/', DS, $result);
		$this->assertEquals(array(DS . 'path' . DS . 'to' . DS . 'files', WWW_ROOT . 'js'), $result);
	}

	public function testAddTarget() {
		$this->config->addTarget('testing.js', array('one.js', 'two.js'));
		$this->assertEquals(array('one.js', 'two.js'), $this->config->files('testing.js'));

		$this->config->addTarget('testing-two.js', array(
			'files' => array('one.js', 'two.js'),
			'filters' => array('uglify'),
			'theme' => true
		));
		$this->assertEquals(array('one.js', 'two.js'), $this->config->files('testing-two.js'));
	}

	public function testGetExt() {
		$this->assertEquals('js', $this->config->getExt('foo.bar.js'));
		$this->assertEquals('css', $this->config->getExt('something.less.css'));
	}

	public function testCachePath() {
		$this->config->cachePath('js', 'WEBROOT/css_build');
		$this->assertEquals(WWW_ROOT . 'css_build', $this->config->cachePath('js'));
	}

	public function testFilterConfig() {
		$result = $this->config->filterConfig('uglify');
		$expected = array('path' => '/path/to/uglify-js');
		$this->assertEquals($expected, $result);

		$this->config->filterConfig('sprockets', array('some' => 'value'));
		$this->assertEquals(array('some' => 'value'), $this->config->filterConfig('sprockets'));

		$this->assertEquals(array(), $this->config->filterConfig('imaginary'));
	}

	public function testFilterConfigArray() {
		$this->config->filterConfig('sprockets', array('some' => 'value'));

		$result = $this->config->filterConfig(array('uglify', 'sprockets'));
		$expected = array(
			'sprockets' => array(
				'some' => 'value'
			),
			'uglify' => array(
				'path' => '/path/to/uglify-js'
			)
		);
		$this->assertEquals($expected, $result);
	}

	public function testTargets() {
		$this->assertEquals(array(), $this->config->targets('fake'));
		$expected = array('libs.js', 'foo.bar.js', 'new_file.js');
		$result = $this->config->targets('js');
		$this->assertEquals($expected, $result);

		$expected = array('all.css', 'pink.css');
		$result = $this->config->targets('css');
		$this->assertEquals($expected, $result);
	}

	public function testGet() {
		$result = $this->config->get('js.cachePath');
		$this->assertEquals(WWW_ROOT . 'cache_js', $result);

		$this->assertNull($this->config->get('Bogus.poop'));
	}

	public function testSet() {
		$this->assertNull($this->config->get('Bogus.poop'));
		$this->config->set('Bogus.poop', 'smelly');
		$this->assertEquals('smelly', $this->config->get('Bogus.poop'));
	}

	public function testSetLimit() {
		try {
			$this->config->set('only.two.allowed', 'smelly');
			$this->assertFalse(true, 'No exception');
		} catch (RuntimeException $e) {
			$this->assertTrue(true, 'Exception was raised.');
		}
	}

	public function testExtensions() {
		$result = $this->config->extensions();
		$this->assertEquals(array('js', 'css'), $result);
	}

	public function testGeneral() {
		$this->config->set('general.cacheConfig', true);
		$result = $this->config->general('cacheConfig');
		$this->assertTrue($result);

		$result = $this->config->general('non-existant');
		$this->assertNull($result);
	}

/**
 * Test that the default paths work.
 *
 */
	public function testDefaultConventions() {
		$ini = dirname($this->testConfig) . DS . 'bare.ini';
		$config = AssetConfig::buildFromIniFile($ini);

		$result = $config->paths('js');
		$this->assertEquals(array(WWW_ROOT . 'js/**'), $result);

		$result = $config->paths('css');
		$this->assertEquals(array(WWW_ROOT . 'css/**'), $result);
	}

	public function testTheme() {
		$result = $this->config->theme();
		$this->assertEquals('', $result);

		$result = $this->config->theme('red');
		$this->assertEquals('', $result);

		$result = $this->config->theme();
		$this->assertEquals('red', $result);
	}

	public function testIsThemed() {
		$this->assertFalse($this->config->isThemed('libs.js'));

		$config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$this->assertTrue($config->isThemed('themed.css'));
	}

}
