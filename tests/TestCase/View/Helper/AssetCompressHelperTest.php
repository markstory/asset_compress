<?php
namespace AssetCompress\Test\TestCase\View\Helper;

use AssetCompress\View\Helper\AssetCompressHelper;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\View;
use Cake\TestSuite\TestCase;
use MiniAsset\AssetConfig;

class AssetCompressHelperTest extends TestCase
{

    /**
     * start a test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->_testFiles = APP;
        $testFile = APP . 'config' . DS . 'integration.ini';

        $controller = null;
        $request = new Request();

        $view = new View($controller);
        $view->request = $request;
        $this->Helper = new AssetCompressHelper($view, ['noconfig' => true]);
        $config = AssetConfig::buildFromIniFile($testFile, [
            'TEST_FILES' => APP,
            'WEBROOT' => WWW_ROOT
        ]);
        $this->Helper->assetConfig($config);

        Router::reload();
    }

    /**
     * end a test
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Helper);
    }

    /**
     * Test that generated elements can have attributes added.
     *
     * @return void
     */
    public function testAttributesOnElements()
    {
        $result = $this->Helper->script('libs.js', ['defer' => true]);

        $expected = [
            ['script' => [
                'defer' => 'defer',
                'src' => '/cache_js/libs.js'
            ]]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Helper->css('all.css', ['test' => 'value']);
        $expected = [
            'link' => [
                'test' => 'value',
                'rel' => 'stylesheet',
                'href' => '/cache_css/all.css'
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that a baseurl configuration works well.
     *
     * @return void
     */
    public function testBaseUrl()
    {
        Configure::write('debug', false);
        $config = $this->Helper->assetConfig();
        $config->set('js.baseUrl', 'http://cdn.example.com/js/');
        $config->set('js.timestamp', false);

        $result = $this->Helper->script('libs.js');
        $expected = [
            ['script' => [
                'src' => 'http://cdn.example.com/js/libs.js'
            ]]
        ];
        $this->assertHtml($expected, $result);

        Configure::write('debug', 1);
        $result = $this->Helper->script('libs.js');
        $expected = [
            ['script' => [
                'src' => '/cache_js/libs.js'
            ]]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that builds using themes defined in the ini file work
     * with themes.
     *
     * @return void
     */
    public function testDefinedBuildWithThemeNoBuiltAsset()
    {
        $this->Helper->theme = 'blue';
        $config = $this->Helper->assetConfig();
        $config->addTarget('themed.js', [
            'theme' => true,
            'files' => ['base.js']
        ]);
        $result = $this->Helper->script('themed.js');
        $expected = [
            ['script' => [
                'src' => '/cache_js/themed.js?theme=blue'
            ]]
        ];
        $this->assertHtml($expected, $result);
    }

    public function testRawAssets()
    {
        $config = $this->Helper->assetConfig();
        $config->addTarget('raw.js', [
            'files' => ['classes/base_class.js', 'classes/base_class_two.js']
        ]);

        $result = $this->Helper->script('raw.js', ['raw' => true]);
        $expected = [
            [
                'script' => [
                    'src' => '/js/classes/base_class.js'
                ],
            ],
            '/script',
            [
                'script' => [
                    'src' => '/js/classes/base_class_two.js'
                ],
            ],
            '/script',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test creating production URLs with plugin assets.
     *
     * @return void
     */
    public function testUrlGenerationProductionModePluginIni()
    {
        Configure::write('debug', false);

        $config = new AssetConfig([], [
            'WEBROOT/' => WWW_ROOT
        ]);
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.ini', 'TestAssetIni.');
        $config->paths('css', null, [
            $this->_testFiles . 'css' . DS
        ]);
        $config->paths('js', null, [
            $this->_testFiles . 'js' . DS
        ]);
        $config->cachePath('js', '/cache_js/');
        $this->Helper->assetConfig($config);

        $result = $this->Helper->script('TestAssetIni.libs.js');
        $expected = [
            ['script' => [
                'src' => '/cache_js/TestAssetIni.libs.js'
            ]]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test raw assets from plugins.
     *
     * @return void
     */
    public function testRawAssetsPlugin()
    {
        Plugin::load('TestAsset');

        $config = AssetConfig::buildFromIniFile($this->_testFiles . 'config/plugins.ini');
        $config->paths('css', null, [
            $this->_testFiles . 'css' . DS
        ]);
        $config->paths('js', null, [
            $this->_testFiles . 'js' . DS
        ]);
        $this->Helper->assetConfig($config);

        $result = $this->Helper->css('plugins.css', ['raw' => true]);
        $expected = [
            [
                'link' => [
                    'rel' => 'stylesheet',
                    'href' => 'preg:/.*css\/nav.css/'
                ]
            ],
            [
                'link' => [
                    'rel' => 'stylesheet',
                    'href' => '/test_asset/plugin.css'
                ]
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Helper->script('plugins.js', ['raw' => true]);
        $expected = [
            [
                'script' => [
                    'src' => '/test_asset/plugin.js'
                ]
            ]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test compiled builds with themes.
     *
     * @return void
     */
    public function testCompiledBuildWithThemes()
    {
        Configure::write('debug', false);
        $config = $this->Helper->assetConfig();
        $config->cachePath('js', TMP);
        $config->addTarget('asset_test.js', [
            'files' => ['base.js'],
            'theme' => true
        ]);

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
    public function testUrlBasic()
    {
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
    public function testUrlProductionMode()
    {
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
    public function testUrlFullOption()
    {
        $result = $this->Helper->url('libs.js', ['full' => true]);
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
    public function testUrlWithBaseUrlAndTimestamp()
    {
        Configure::write('debug', 0);
        $config = $this->Helper->assetConfig();
        $config->set('js.baseUrl', 'http://cdn.example.com/js/');
        $config->set('js.timestamp', true);

        $result = $this->Helper->url('libs.js');
        $expected = '#^http://cdn\.example\.com/js/libs\.v\d+\.js$#';
        $this->assertRegExp($expected, $result);
    }

    /**
     * Test exceptions when getting URLs
     *
     * @expectedException Exception
     * @expectedExceptionMessage Cannot get URL for build file that does not exist.
     */
    public function testUrlError()
    {
        $this->Helper->url('nope.js');
    }

    /**
     * test in development script links are created
     *
     * @return void
     */
    public function testInlineCssDevelopment()
    {
        $config = $this->Helper->assetConfig();
        $config->paths('css', null, [
            $this->_testFiles . 'css' . DS
        ]);

        $config->addTarget('nav.css', [
            'files' => ['nav.css']
        ]);

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
    public function testInlineCss()
    {
        $config = $this->Helper->assetConfig();
        $config->paths('css', null, [
            $this->_testFiles . 'css' . DS
        ]);

        $config->addTarget('nav.css', [
            'files' => ['nav.css']
        ]);

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
    public function testInlineCssMultiple()
    {
        $config = $this->Helper->assetConfig();
        $config->paths('css', null, [
            $this->_testFiles . 'css' . DS
        ]);

        $config->addTarget('nav.css', [
            'files' => ['nav.css', 'has_import.css']
        ]);
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
    public function testInlineScriptDevelopment()
    {
        Configure::write('debug', 1);
        $results = $this->Helper->inlineScript('libs.js');

        $expected = <<<EOF
<script>var BaseClass = new Class({

});

var BaseClass = new Class({

});

var Template = new Class({

});</script>
EOF;

        $this->assertEquals($expected, $results);
    }

    /**
     * test inline javascript is generated
     *
     * @return void
     */
    public function testInlineScript()
    {
        Configure::write('debug', 0);

        $expected = <<<EOF
<script>var BaseClass = new Class({

});

var BaseClass = new Class({

});

var Template = new Class({

});</script>
EOF;

        $result = $this->Helper->inlineScript('libs.js');
        $this->assertEquals($expected, $result);
    }

    /**
     * test no conflict with plugin names
     *
     * @return void
     */
    public function testNoConflictWithPluginName()
    {
        Plugin::load('Blue');

        $result = $this->Helper->script('blue-app.js', ['raw' => true]);
        $expected = [
            [
                'script' => [
                    'src' => '/js/BlueController.js'
                ]
            ]
        ];
        $this->assertHtml($expected, $result);
    }
}
