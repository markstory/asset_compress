<?php
namespace AssetCompress\Test\TestCase\View\Helper;

use AssetCompress\AssetConfig;
use AssetCompress\View\Helper\AssetCompressHelper;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\View\View;
use Cake\TestSuite\TestCase;

class AssetCompressHelperPluginIniTest extends TestCase
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
        $testFile = $this->_testFiles . 'config' . DS . 'integration.ini';

        Plugin::load('TestAssetIni');

        $controller = null;
        $request = new Request();
        $request->webroot = '';
        $view = new View($controller);
        $view->request = $request;
        $this->Helper = new AssetCompressHelper($view, array('noconfig' => true));
        $config = AssetConfig::buildFromIniFile($testFile, [
            'TEST_FILES/' => APP,
            'WEBROOT/' => WWW_ROOT
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

        Plugin::unload('TestAssetIni');
    }

    public function testUrlGenerationProductionModePluginIni()
    {
        Configure::write('debug', false);
        $this->Helper->assetConfig()->set('js.timestamp', false);

        $result = $this->Helper->script('TestAssetIni.libs.js');
        $expected = array(
            array('script' => array(
                'src' => '/cache_js/TestAssetIni.libs.js'
            ))
        );
        $this->assertHtml($expected, $result);
    }
}
