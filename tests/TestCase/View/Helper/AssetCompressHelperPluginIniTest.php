<?php
namespace AssetCompress\Test\TestCase\View\Helper;

use AssetCompress\AssetConfig;
use AssetCompress\View\Helper\AssetCompressHelper;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\View;
use Cake\TestSuite\TestCase;

class AssetCompressHelperPluginIniTest extends TestCase {

/**
 * start a test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_testFiles = APP;
		$testFile = $this->_testFiles . 'config' . DS . 'config.ini';

		Plugin::load('TestAssetIni');

		AssetConfig::clearAllCachedKeys();

		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'path' => TMP,
			'prefix' => 'asset_compress_test_',
			'engine' => 'File'
		));

		$controller = null;
		$request = new Request();
		$request->webroot = '';
		$view = new View($controller);
		$view->request = $request;
		$this->Helper = new AssetCompressHelper($view, array('noconfig' => true));
		$Config = AssetConfig::buildFromIniFile($testFile);
		$this->Helper->assetConfig($Config);

		Router::reload();
	}

/**
 * end a test
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Helper);

		AssetConfig::clearAllCachedKeys();
		Plugin::unload('TestAssetIni');
	}

	public function testUrlGenerationProductionModePluginIni() {
		Configure::write('debug', false);
		$this->Helper->assetConfig()->set('js.timestamp', false);

		$result = $this->Helper->script('TestAssetIni.libs.js');
		$expected = array(
			array('script' => array(
				'src' => '/cache_js/TestAssetIni.libs.js'
			))
		);
		$this->assertTags($result, $expected);
	}

}
