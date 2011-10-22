<?php
App::import('Libs', 'AssetCompress.AssetConfig');

class AssetConfigTest extends CakeTestCase {

	function setUp() {
		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'engine' => 'File'
		));

		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = App::pluginPath('AssetCompress') . 'tests' . DS . 'test_files' . DS;
		$this->testConfig = $this->_testFiles . 'config' . DS . 'config.ini';
		$this->_themeConfig = $this->_testFiles . 'config' . DS . 'themed.ini';

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($this->testConfig);
	}

	function testBuildFromIniFile() {
		$config = AssetConfig::buildFromIniFile($this->testConfig);
		$this->assertTrue($config->get('js.timestamp'));
		$this->assertTrue($config->general('writeCache'));
	}

	function testExceptionOnBogusFile() {
		try {
			$config = AssetConfig::buildFromIniFile('/bogus');
			$this->assertFalse(true, 'Exception not thrown.');
		} catch (Exception $e) {
			$this->assertEqual('Configuration file "/bogus" was not found.', $e->getMessage());
		}
	}

	function testFilters() {
		$result = $this->config->filters('js');
		$this->assertEqual(array('sprockets', 'jsyuicompressor'), $result);

		$result = $this->config->filters('js', 'libs.js');
		$this->assertEqual(array('sprockets', 'jsyuicompressor', 'uglify'), $result);

		$this->assertEqual(array(), $this->config->filters('nothing'));
	}

	function testSettingFilters() {
		$this->config->filters('js', null, array('uglify'));
		$this->assertEqual(array('uglify'), $this->config->filters('js'));
		$this->assertEqual(array('uglify'), $this->config->filters('js', 'libs.js'));

		$this->config->filters('js', 'libs.js', array('sprockets'));
		$this->assertEqual(array('uglify'), $this->config->filters('js'));
		$this->assertEqual(array('uglify', 'sprockets'), $this->config->filters('js', 'libs.js'));
	}

	function testFiles() {
		$result = $this->config->files('libs.js');
		$expected = array('jquery.js', 'mootools.js', 'class.js');
		$this->assertEqual($expected, $result);

		$result = $this->config->files('foo.bar.js');
		$expected = array('test.js');
		$this->assertEqual($expected, $result);

		$this->assertEqual(array(), $this->config->files('nothing here'));
	}

	function testSettingFiles() {
		$this->config->files('new_build.js', array('one.js', 'two.js'));

		$this->assertEqual(array('one.js', 'two.js'), $this->config->files('new_build.js'));
	}

	function testPathConstantReplacement() {
		$result = $this->config->paths('css');
		$this->assertEqual(array(WWW_ROOT . 'css' . DS), $result);
		$this->assertEqual(array(), $this->config->paths('nothing'));
	}

	function testPaths() {
		$this->config->paths('js', array('/path/to/files', 'WEBROOT/js'));
		$this->assertEqual(array('/path/to/files', WWW_ROOT . 'js'), $this->config->paths('js'));
	}

	function testAddTarget() {
		$this->config->addTarget('testing.js', array('one.js', 'two.js'));
		$this->assertEqual(array('one.js', 'two.js'), $this->config->files('testing.js'));

		$this->config->addTarget('testing-two.js', array(
			'files' => array('one.js', 'two.js'),
			'filters' => array('uglify'),
			'theme' => true
		));
		$this->assertEqual(array('one.js', 'two.js'), $this->config->files('testing-two.js'));
	}

	function testGetExt() {
		$this->assertEqual('js', $this->config->getExt('foo.bar.js'));
		$this->assertEqual('css', $this->config->getExt('something.less.css'));
	}

	function testCachePath() {
		$this->config->cachePath('js', 'WEBROOT/css_build');
		$this->assertEqual(WWW_ROOT . 'css_build', $this->config->cachePath('js'));
	}

	function testFilterConfig() {
		$result = $this->config->filterConfig('uglify');
		$expected = array('path' => '/path/to/uglify-js');
		$this->assertEqual($result, $expected);

		$this->config->filterConfig('sprockets', array('some' => 'value'));
		$this->assertEqual(array('some' => 'value'), $this->config->filterConfig('sprockets'));

		$this->assertEqual(array(), $this->config->filterConfig('imaginary'));
	}

	function testFilterConfigArray() {
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
		$this->assertEqual($result, $expected);
	}

	function testTargets() {
		$this->assertEqual(array(), $this->config->targets('fake'));
		$expected = array('libs.js', 'foo.bar.js', 'new_file.js');
		$result = $this->config->targets('js');
		$this->assertEqual($expected, $result);

		$expected = array('all.css', 'pink.css');
		$result = $this->config->targets('css');
		$this->assertEqual($expected, $result);
	}

	function testGet() {
		$result = $this->config->get('js.cachePath');
		$this->assertEqual(WWW_ROOT . 'cache_js', $result);

		$this->assertNull($this->config->get('Bogus.poop'));
	}

	function testSet() {
		$this->assertNull($this->config->get('Bogus.poop'));
		$this->config->set('Bogus.poop', 'smelly');
		$this->assertEqual('smelly', $this->config->get('Bogus.poop'));
	}

	function testSetLimit() {
		try {
			$this->config->set('only.two.allowed', 'smelly');
			$this->assertFalse(true, 'No exception');
		} catch (RuntimeException $e) {
			$this->assertTrue(true, 'Exception was raised.');
		}
	}

	function testCachingOn() {
		$this->config->general('writeCache', false);
		$this->assertFalse($this->config->cachingOn('libs.js'));

		$this->config->general('writeCache', true);
		$this->config->cachePath('js', '/some/path');
		$this->assertTrue($this->config->cachingOn('libs.js'));
	}



	function testExtensions() {
		$result = $this->config->extensions();
		$this->assertEqual(array('js', 'css'), $result);
	}

	function testGeneral() {
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
	function testDefaultConventions() {
		$ini = dirname($this->testConfig) . DS . 'bare.ini';
		$config = AssetConfig::buildFromIniFile($ini);

		$result = $config->paths('js');
		$this->assertEqual(array(WWW_ROOT . 'js/**'), $result);

		$result = $config->paths('css');
		$this->assertEqual(array(WWW_ROOT . 'css/**'), $result);
	}

	function testTheme() {
		$result = $this->config->theme();
		$this->assertEqual('', $result);

		$result = $this->config->theme('red');
		$this->assertEqual('', $result);

		$result = $this->config->theme();
		$this->assertEqual('red', $result);
	}

	function testIsThemed() {
		$this->assertFalse($this->config->isThemed('libs.js'));

		$config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$this->assertTrue($config->isThemed('themed.css'));
	}

}
