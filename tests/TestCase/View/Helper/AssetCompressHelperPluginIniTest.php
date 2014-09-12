<?php
namespace AssetCompress\Test\TestCase\View\Helper;
use AssetCompress\AssetConfig;
use AssetCompress\View\Helper\AssetCompressHelper;

use App\View\Helper\HtmlHelper;
use Cake\View\View;


class AssetCompressHelperPluginIniTest extends CakeTestCase {

/**
 * start a test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = $this->_pluginPath . 'Test' . DS . 'test_files' . DS;
		$testFile = $this->_testFiles . 'Config' . DS . 'config.ini';

		App::build(array(
			'Plugin' => array($this->_testFiles . 'Plugin' . DS)
		));
		Plugin::load('TestAssetIni');

		AssetConfig::clearAllCachedKeys();

		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'path' => TMP,
			'prefix' => 'asset_compress_test_',
			'engine' => 'File'
		));

		$controller = null;
		$request = new Request(null, false);
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
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Helper);

		Cache::delete(AssetConfig::CACHE_BUILD_TIME_KEY, AssetConfig::CACHE_CONFIG);
		Cache::drop(AssetConfig::CACHE_CONFIG);
		// @codingStandardsIgnoreStart
		@unlink(TMP . AssetConfig::BUILD_TIME_FILE);
		// @codingStandardsIgnoreEnd

		Plugin::unload('TestAssetIni');
	}

	public function testUrlGenerationProductionModePluginIni() {
		Configure::write('debug', 0);
		$this->Helper->config()->set('js.timestamp', false);

		$result = $this->Helper->script('TestAssetIni.libs.js');
		$expected = array(
			array('script' => array(
				'type' => 'text/javascript',
				'src' => '/cache_js/TestAssetIni.libs.js'
			))
		);
		$this->assertTags($result, $expected);
	}

}
