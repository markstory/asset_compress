<?php
App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('AssetCompressHelper', 'AssetCompress.View/Helper');

App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');


class AssetCompressHelperTest extends CakeTestCase {
/**
 * start a test
 *
 * @return void
 **/
	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = $this->_pluginPath . 'Test' . DS . 'test_files' . DS;
		$testFile = $this->_testFiles . 'Config' . DS . 'config.ini';

		AssetConfig::clearAllCachedKeys();

		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'path' => TMP,
			'prefix' => 'asset_compress_test_',
			'engine' => 'File'
		));

		$controller = null;
		$request = new CakeRequest(null, false);
		$request->webroot = '';
		$view = new View($controller);
		$view->request = $request;
		$this->Helper = new AssetCompressHelper($view, array('noconfig' => true));
		$Config = AssetConfig::buildFromIniFile($testFile);
		$this->Helper->config($Config);
		$this->Helper->Html = new HtmlHelper($view);

		Router::reload();
		Configure::write('debug', 2);
	}

/**
 * end a test
 *
 * @return void
 **/
	public function tearDown() {
		parent::tearDown();
		unset($this->Helper);

		Cache::delete(AssetConfig::CACHE_BUILD_TIME_KEY, AssetConfig::CACHE_CONFIG);
		Cache::drop(AssetConfig::CACHE_CONFIG);
		@unlink(TMP . AssetConfig::BUILD_TIME_FILE);
	}

/**
 * test that assets only have one base path attached
 *
 * @return void
 */
	public function testIncludeAssets() {
		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'index', 'plugin' => null),
			array('base' => '/some/dir', 'webroot' => '/some/dir/', 'here' => '/some/dir/posts')
		));
		$this->Helper->Html->webroot = '/some/dir/';

		$this->Helper->addScript('one.js');
		$result = $this->Helper->includeAssets();
		$this->assertRegExp('#"/some/dir/asset_compress#', $result, 'double dir set %s');
	}

/**
 * test that setting $compress = false echos original scripts
 *
 * @return void
 */
	public function testNoCompression() {
		$this->Helper->addCss('one', 'lib');
		$this->Helper->addCss('two');
		$this->Helper->addScript('one');
		$this->Helper->addScript('dir/two');

		$result = $this->Helper->includeAssets(true);
		$expected = array(
			array(
				'link' => array(
					'type' => 'text/css',
					'rel' => 'stylesheet',
					'href' => 'preg:/.*css\/one\.css/'
				)
			),
			array(
				'link' => array(
					'type' => 'text/css',
					'rel' => 'stylesheet',
					'href' => 'preg:/.*css\/two\.css/'
				)
			),
			array(
				'script' => array(
					'type' => 'text/javascript',
					'src' => 'preg:/.*js\/one\.js/'
				)
			),
			'/script',
			array(
				'script' => array(
					'type' => 'text/javascript',
					'src' => 'preg:/.*js\/dir\/two\.js/'
				)
			),
			'/script'
		);
		$this->assertTags($result, $expected, true);
	}

/**
 * test css addition
 *
 * @return void
 */
	public function testCssOrderPreserving() {
		$this->Helper->addCss('base');
		$this->Helper->addCss('reset');

		$hash = md5('base_reset');

		$result = $this->Helper->includeAssets();
		$expected = array(
			'link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/assets/get/' . $hash . '.css?file%5B0%5D=base&amp;file%5B1%5D=reset'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * test script addition
 *
 * @return void
 */
	public function testScriptOrderPreserving() {
		$this->Helper->addScript('libraries');
		$this->Helper->addScript('thing');

		$hash = md5('libraries_thing');

		$result = $this->Helper->includeAssets();
		$expected = array(
			'script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/' . $hash . '.js?file%5B0%5D=libraries&amp;file%5B1%5D=thing'
			),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that magic slug builds work.
 *
 * @return void
 */
	public function testScriptMagicSlugs() {
		$this->Helper->addScript('libraries', ':hash-default');
		$this->Helper->addScript('thing', ':hash-default');
		$this->Helper->addScript('jquery.js', ':hash-jquery');
		$this->Helper->addScript('jquery-ui.js', ':hash-jquery');

		$hash1 = md5('libraries_thing');
		$hash2 = md5('jquery.js_jquery-ui.js');

		$result = $this->Helper->includeAssets();
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/' . $hash1 . '.js?file%5B0%5D=libraries&amp;file%5B1%5D=thing'
			)),
			'/script',
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/' . $hash2 . '.js?file%5B0%5D=jquery.js&amp;file%5B1%5D=jquery-ui.js'
			)),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that build files with magic hash names, are linked in properly.
 *
 */
	public function testMagicHashBuildFileUse() {
		$config = $this->Helper->config();
		$config->general('writeCache', true);
		$config->cachePath('js', TMP);
		$config->set('js.timestamp', false);

		$this->Helper->addScript('libraries', ':hash-default');
		$this->Helper->addScript('thing', ':hash-default');

		$hash = md5('libraries_thing');
		touch(TMP . $hash . '.js');

		$result = $this->Helper->includeAssets();
		$this->assertTrue(strpos($result, $hash) !== false);
		$this->assertFalse(strpos($result, '?file'), 'Querystring found, built asset not used.');
		unlink(TMP . $hash . '.js');
	}

/**
 * test generating two script files.
 *
 * @return void
 */
	public function testMultipleScriptFiles() {
		$this->Helper->addScript('libraries', 'default');
		$this->Helper->addScript('thing', 'second');

		$result = $this->Helper->includeAssets();
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/default.js?file%5B0%5D=libraries'
			)),
			'/script',
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/second.js?file%5B0%5D=thing'
			)),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test includeJs() with multiple destination files.
 *
 * @return void
 */
	public function testIncludeJsMultipleDestination() {
		$this->Helper->addScript('libraries', 'default');
		$this->Helper->addScript('thing', 'second');
		$this->Helper->addScript('other', 'third');

		$result = $this->Helper->includeJs('default');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/default.js?file%5B0%5D=libraries'
			)),
		);
		$this->assertTags($result, $expected);

		$result = $this->Helper->includeJs('second', 'third');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/second.js?file%5B0%5D=thing'
			)),
			'/script',
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/third.js?file%5B0%5D=other'
			)),
			'/script'
		);
		$this->assertTags($result, $expected);
	}

/**
 * test includeCss() with multiple destination files.
 *
 * @return void
 */
	public function testIncludeCssMultipleDestination() {
		$this->Helper->addCss('libraries', 'default');
		$this->Helper->addCss('thing', 'second');
		$this->Helper->addCss('other', 'third');

		$result = $this->Helper->includeCss('second', 'default');
		$expected = array(
			array('link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/assets/get/second.css?file%5B0%5D=thing'
			)),
			array('link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/assets/get/default.css?file%5B0%5D=libraries'
			)),
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that including assets removes them from the list of files to be included.
 *
 * @return void
 */
	public function testIncludingFilesRemovesFromQueue() {
		$this->Helper->addCss('libraries', 'default');
		$result = $this->Helper->includeCss('default');
		$expected = array(
			'link' => array(
				'type' => 'text/css',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/assets/get/default.css?file%5B0%5D=libraries'
			)
		);
		$this->assertTags($result, $expected);

		$result = $this->Helper->includeCss('default');
		$this->assertEquals('', $result);
	}

/**
 * Test that build files are correctly linked in when they exist on cachePath.
 */
	public function testLinkingBuiltFiles() {
		$config = $this->Helper->config();
		$config->general('writeCache', true);
		$config->set('js.timestamp', false);
		$config->cachePath('js', TMP);
		$config->files('asset_test.js', array('one.js'));

		touch(TMP . 'asset_test.js');

		$result = $this->Helper->script('asset_test.js');
		$result = str_replace('/', DS, $result);
		$this->assertTrue(strpos($result, TMP . 'asset_test.js') !== false);
		unlink(TMP . 'asset_test.js');
	}


/**
 * Test that generated elements can have attributes added.
 *
 */
	public function testAttributesOnElements() {
		$result = $this->Helper->script('libs.js', array('defer' => true));

		$expected = array(
			array('script' => array(
				'defer' => 'defer',
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/libs.js'
			))
		);
		$this->assertTags($result, $expected);

		$result = $this->Helper->css('all.css', array('test' => 'value'));
		$expected = array(
			'link' => array(
				'type' => 'text/css',
				'test' => 'value',
				'rel' => 'stylesheet',
				'href' => '/asset_compress/assets/get/all.css'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * test timestamping assets.
 *
 * @return void
 */
	public function testTimestampping() {
		$config = $this->Helper->config();
		$config->general('writeCache', true);
		$config->set('js.timestamp', true);
		$config->cachePath('js', TMP);
		$config->files('asset_test.js', array('one.js'));

		$time = time();
		$cache = $this->Helper->cache();
		$cache->setTimestamp('asset_test.js', $time);

		$filename = TMP . 'asset_test.v' . $time . '.js';
		touch($filename);

		$result = $this->Helper->script('asset_test.js');
		$result = str_replace('/', DS, $result);
		$this->assertTrue(strpos($result, $filename) !== false);
		unlink($filename);
	}

/**
 * test that a baseurl configuration works well.
 *
 * @return void
 */
	public function testBaseUrl() {
		Configure::write('debug', 0);
		$config = $this->Helper->config();
		$config->set('js.baseUrl', 'http://cdn.example.com/js/');
		$config->set('js.timestamp', false);

		$result = $this->Helper->script('libs.js');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => 'http://cdn.example.com/js/libs.js'
			))
		);
		$this->assertTags($result, $expected);

		Configure::write('debug', 1);
		$result = $this->Helper->script('libs.js');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/libs.js'
			))
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that baseurl and timestamps play nice.
 *
 * @return void
 */
	public function testBaseUrlAndTimestamp() {
		Configure::write('debug', 0);
		$config = $this->Helper->config();
		$config->set('js.baseUrl', 'http://cdn.example.com/js/');
		$config->set('js.timestamp', true);
		$config->general('cacheConfig', true);

		// populate the cache.
		Cache::write(AssetConfig::CACHE_BUILD_TIME_KEY, array('libs.js' => 1234), AssetConfig::CACHE_CONFIG);

		$result = $this->Helper->script('libs.js');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => 'http://cdn.example.com/js/libs.v1234.js'
			))
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test that builds using themes defined in the ini file work
 * with themes.
 *
 * @return void
 */
	public function testDefinedBuildWithThemeNoBuiltAsset() {
		$this->Helper->theme = 'blue';
		$config = $this->Helper->config();
		$config->addTarget('themed.js', array(
			'theme' => true,
			'files' => array('libraries.js')
		));
		$result = $this->Helper->script('themed.js');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/asset_compress/assets/get/themed.js?theme=blue'
			))
		);
		$this->assertTags($result, $expected);
	}

	public function testCompiledBuildWithThemes() {
		$config = $this->Helper->config();
		$config->general('writeCache', true);
		$config->set('js.timestamp', false);
		$config->cachePath('js', TMP);
		$config->addTarget('asset_test.js', array(
			'files' => array('one.js'),
			'theme' => true
		));

		touch(TMP . 'blue-asset_test.js');

		$this->Helper->theme = 'blue';
		$result = $this->Helper->script('asset_test.js');
		$result = str_replace('/', DS, $result);
		$this->assertTrue(strpos($result, TMP . 'blue-asset_test.js') !== false);
		unlink(TMP . 'blue-asset_test.js');
	}

	public function testRawAssets() {
		$result = $this->Helper->script('new_file.js', array('raw' => true));
		$expected = array(
			array(
				'script' => array(
					'type' => 'text/javascript',
					'src' => 'js/prototype.js'
				),
			),
			'/script',
			array(
				'script' => array(
					'type' => 'text/javascript',
					'src' => 'js/scriptaculous.js'
				),
			),
			'/script',
		);
		$this->assertTags($result, $expected);
	}

	public function testRawAssetsPlugin() {
		App::build(array(
			'Plugin' => array($this->_testFiles . 'Plugin' . DS)
		));
		CakePlugin::load('TestAsset');
		$config = AssetConfig::buildFromIniFile($this->_testFiles . 'Config/plugins.ini');
		$this->Helper->config($config);

		$result = $this->Helper->css('plugins.css', array('raw' => true));
		$expected = array(
			array(
				'link' => array(
					'type' => 'text/css',
					'rel' => 'stylesheet',
					'href' => 'css/nav.css'
				)
			),
			array(
				'link' => array(
					'type' => 'text/css',
					'rel' => 'stylesheet',
					'href' => '/test_asset/plugin.css'
				)
			),
		);
		$this->assertTags($result, $expected);
	}
}
