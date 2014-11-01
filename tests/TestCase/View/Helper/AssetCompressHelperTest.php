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

class AssetCompressHelperTest extends TestCase {

/**
 * start a test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_testFiles = APP;
		$testFile = APP . 'config' . DS . 'config.ini';

		AssetConfig::clearAllCachedKeys();

		Cache::drop(AssetConfig::CACHE_CONFIG);
		Cache::config(AssetConfig::CACHE_CONFIG, array(
			'path' => TMP,
			'prefix' => 'asset_compress_test_',
			'engine' => 'File'
		));

		$controller = null;
		$request = new Request();

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
		Cache::drop(AssetConfig::CACHE_CONFIG);
	}

/**
 * Test that generated elements can have attributes added.
 *
 * @return void
 */
	public function testAttributesOnElements() {
		$result = $this->Helper->script('libs.js', array('defer' => true));

		$expected = array(
			array('script' => array(
				'defer' => 'defer',
				'src' => '/cache_js/libs.js'
			))
		);
		$this->assertTags($result, $expected);

		$result = $this->Helper->css('all.css', array('test' => 'value'));
		$expected = array(
			'link' => array(
				'test' => 'value',
				'rel' => 'stylesheet',
				'href' => '/cache_css/all.css'
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * test that a baseurl configuration works well.
 *
 * @return void
 */
	public function testBaseUrl() {
		Configure::write('debug', false);
		$config = $this->Helper->assetConfig();
		$config->set('js.baseUrl', 'http://cdn.example.com/js/');
		$config->set('js.timestamp', false);

		$result = $this->Helper->script('libs.js');
		$expected = array(
			array('script' => array(
				'src' => 'http://cdn.example.com/js/libs.js'
			))
		);
		$this->assertTags($result, $expected);

		Configure::write('debug', 1);
		$result = $this->Helper->script('libs.js');
		$expected = array(
			array('script' => array(
				'src' => '/cache_js/libs.js'
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
		$config = $this->Helper->assetConfig();
		$config->addTarget('themed.js', array(
			'theme' => true,
			'files' => array('libraries.js')
		));
		$result = $this->Helper->script('themed.js');
		$expected = array(
			array('script' => array(
				'src' => '/cache_js/themed.js?theme=blue'
			))
		);
		$this->assertTags($result, $expected);
	}

	public function testRawAssets() {
		$config = $this->Helper->assetConfig();
		$config->addTarget('raw.js', array(
			'files' => array('classes/base_class.js', 'classes/base_class_two.js')
		));
		$config->paths('js', null, array(
			$this->_testFiles . 'js' . DS
		));

		$result = $this->Helper->script('raw.js', array('raw' => true));
		$expected = array(
			array(
				'script' => array(
					'src' => 'js/classes/base_class.js'
				),
			),
			'/script',
			array(
				'script' => array(
					'src' => 'js/classes/base_class_two.js'
				),
			),
			'/script',
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test raw assets from plugins.
 *
 * @return void
 */
	public function testRawAssetsPlugin() {
		Plugin::load('TestAsset');

		$config = AssetConfig::buildFromIniFile($this->_testFiles . 'config/plugins.ini');
		$config->paths('css', null, array(
			$this->_testFiles . 'css' . DS
		));
		$config->paths('js', null, array(
			$this->_testFiles . 'js' . DS
		));
		$this->Helper->assetConfig($config);

		$result = $this->Helper->css('plugins.css', array('raw' => true));
		$expected = array(
			array(
				'link' => array(
					'rel' => 'stylesheet',
					'href' => 'preg:/.*css\/nav.css/'
				)
			),
			array(
				'link' => array(
					'rel' => 'stylesheet',
					'href' => '/test_asset/plugin.css'
				)
			),
		);
		$this->assertTags($result, $expected);

		$result = $this->Helper->script('plugins.js', array('raw' => true));
		$expected = array(
			array(
				'script' => array(
					'src' => '/test_asset/plugin.js'
				)
			)
		);
		$this->assertTags($result, $expected);
	}

/**
 * Test compiled builds with themes.
 *
 * @return void
 */
	public function testCompiledBuildWithThemes() {
		Configure::write('debug', false);
		$config = $this->Helper->assetConfig();
		$config->general('writeCache', true);
		$config->set('js.timestamp', false);
		$config->cachePath('js', TMP);
		$config->addTarget('asset_test.js', array(
			'files' => array('one.js'),
			'theme' => true
		));

		$this->Helper->theme = 'blue';
		$result = $this->Helper->script('asset_test.js');
		$result = str_replace('/', DS, $result);
		$this->assertContains('blue-asset_test.js', $result);
	}

/**
 * Test basic URL generation.
 *
 * @return void
 */
	public function testUrlBasic() {
		$url = $this->Helper->url('all.css');
		$this->assertEquals('/cache_css/all.css', $url);

		$url = $this->Helper->url('libs.js');
		$this->assertEquals('/cache_js/libs.js', $url);
	}

/**
 * Test URL generation in production mode.
 *
 * @return void
 */
	public function testUrlProductionMode() {
		Configure::write('debug', false);
		$this->Helper->assetConfig()->set('js.timestamp', false);

		$result = $this->Helper->url('libs.js');
		$this->assertEquals('/cache_js/libs.js', $result);
	}

/**
 * Test URL generation with full base option.
 *
 * @return void
 */
	public function testUrlFullOption() {
		$result = $this->Helper->url('libs.js', array('full' => true));
		$this->assertEquals(
			'http://localhost/cache_js/libs.js',
			$result
		);

		$result = $this->Helper->url('libs.js', true);
		$this->assertEquals(
			'http://localhost/cache_js/libs.js',
			$result
		);
	}

/**
 * test that baseurl and timestamps play nice.
 *
 * @return void
 */
	public function testUrlWithBaseUrlAndTimestamp() {
		Configure::write('debug', 0);
		$config = $this->Helper->assetConfig();
		$config->set('js.baseUrl', 'http://cdn.example.com/js/');
		$config->set('js.timestamp', true);
		$config->general('cacheConfig', true);

		// populate the cache.
		Cache::write(AssetConfig::CACHE_BUILD_TIME_KEY, array('libs.js' => 1234), AssetConfig::CACHE_CONFIG);

		$result = $this->Helper->url('libs.js');
		$expected = 'http://cdn.example.com/js/libs.v1234.js';
		$this->assertEquals($expected, $result);
	}

/**
 * Test exceptions when getting URLs
 *
 * @expectedException Exception
 * @expectedExceptionMessage Cannot get URL for build file that does not exist.
 */
	public function testUrlError() {
		$this->Helper->url('nope.js');
	}

/**
 * test in development script links are created
 *
 * @return void
 */
	public function testInlineCssDevelopment() {
		$config = $this->Helper->assetConfig();
		$config->paths('css', null, array(
			$this->_testFiles . 'css' . DS
		));

		$config->addTarget('nav.css', array(
			'files' => array('nav.css')
		));

		Configure::write('debug', true);
		$results = $this->Helper->inlineCss('nav.css');

		$expected = <<<EOF
<style type="text/css">@import url("reset/reset.css");
#nav {
	width:100%;
}</style>
EOF;
		$this->assertEquals($expected, $results);
	}

/**
 * test inline css is generated
 *
 * @return void
 */
	public function testInlineCss() {
		$config = $this->Helper->assetConfig();
		$config->paths('css', null, array(
			$this->_testFiles . 'css' . DS
		));

		$config->addTarget('nav.css', array(
			'files' => array('nav.css')
		));

		Configure::write('debug', false);

		$expected = <<<EOF
<style type="text/css">@import url("reset/reset.css");
#nav {
	width:100%;
}</style>
EOF;

		$result = $this->Helper->inlineCss('nav.css');
		$this->assertEquals($expected, $result);
	}

/**
 * test inlineCss() with multiple input files.
 *
 * @return void
 */
	public function testInlineCssMultiple() {
		$config = $this->Helper->assetConfig();
		$config->paths('css', null, array(
			$this->_testFiles . 'css' . DS
		));

		$config->addTarget('nav.css', array(
			'files' => array('nav.css', 'has_import.css')
		));
		Configure::write('debug', false);

		$expected = <<<EOF
<style type="text/css">@import url("reset/reset.css");
#nav {
	width:100%;
}

@import "nav.css";
@import "theme:theme.css";
body {
	color:#f00;
	background:#000;
}</style>
EOF;

		$result = $this->Helper->inlineCss('nav.css');
		$this->assertEquals($expected, $result);
	}

/**
 * test in development script links are created
 *
 * @return void
 */
	public function testInlineScriptDevelopment() {
		$config = $this->Helper->assetConfig();
		$config->set('js.filters', array());

		$config->paths('js', null, array(
			$this->_testFiles . 'js' . DS . 'classes'
		));

		$config->addTarget('all.js', array(
			'files' => array('base_class.js')
		));

		Configure::write('debug', 1);
		$results = $this->Helper->inlineScript('all.js');

		$expected = <<<EOF
<script>var BaseClass = new Class({

});</script>
EOF;

		$this->assertEquals($expected, $results);
	}

/**
 * test inline javascript is generated
 *
 * @return void
 */
	public function testInlineScript() {
		$config = $this->Helper->assetConfig();
		$config->set('js.filters', array());
		$config->paths('js', null, array(
			$this->_testFiles . 'js' . DS . 'classes'
		));

		$config->addTarget('all.js', array(
			'files' => array('base_class.js')
		));

		Configure::write('debug', 0);

		$expected = <<<EOF
<script>var BaseClass = new Class({

});</script>
EOF;

		$result = $this->Helper->inlineScript('all.js');
		$this->assertEquals($expected, $result);
	}

/**
 * test inline javascript for multiple files is generated
 *
 * @return void
 */
	public function testInlineScriptMultiple() {
		$config = $this->Helper->assetConfig();
		$config->set('js.filters', array());
		$config->paths('js', null, array(
			$this->_testFiles . 'js' . DS . 'classes'
		));

		$config->addTarget('all.js', array(
			'files' => array('base_class.js', 'base_class_two.js')
		));

		Configure::write('debug', 0);

		$expected = <<<EOF
<script>var BaseClass = new Class({

});
//= require "base_class"
var BaseClassTwo = BaseClass.extend({

});</script>
EOF;

		$result = $this->Helper->inlineScript('all.js');
		$this->assertEquals($expected, $result);
	}

}
