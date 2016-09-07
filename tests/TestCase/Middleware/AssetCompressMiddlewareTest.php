<?php
namespace AssetCompress\Test\TestCase\Middleware;

use AssetCompress\Middleware\AssetCompressMiddleware;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use MiniAsset\AssetConfig;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class AssetsCompressMiddlewareTest extends TestCase
{

    protected $nextInvoked = false;

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->testConfig = APP . 'config' . DS . 'integration.ini';
        $this->nextInvoked = false;

        $map = [
            'WEBROOT' => WWW_ROOT,
            'TEST_FILES' => APP
        ];
        Plugin::load('TestAssetIni');

        $config = new AssetConfig([], $map);
        $config->load($this->testConfig);
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.ini', 'TestAssetIni.');
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.local.ini', 'TestAssetIni.');

        $this->middleware = new AssetCompressMiddleware($config);
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = function () {
            $this->nextInvoked = true;
        };
    }

    /**
     * teardown method.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload('TestAssetIni');
    }

    /**
     * test building assets interactively
     *
     * @return void
     */
    public function testBuildFile()
    {
        $uri = $this->request->getUri()->withPath('cache_js/libs.js');
        $request = $this->request->withUri($uri);

        $mw = $this->middleware;
        $result = $mw($request, $this->response, $this->next);

        $this->assertEquals('application/javascript', $result->getHeaderLine('Content-Type'));

        $body = $result->getBody()->getContents();
        $this->assertRegExp('/var BaseClass = new Class/', $body);
        $this->assertRegExp('/var Template = new Class/', $body);
    }

    /**
     * test building plugin assets.
     *
     * @return void
     */
    public function testPluginIniBuildFile()
    {
        Plugin::load('TestAssetIni');

        $uri = $this->request->getUri()->withPath('cache_js/TestAssetIni.libs.js');
        $request = $this->request->withUri($uri);

        $mw = $this->middleware;
        $result = $mw($request, $this->response, $this->next);

        $this->assertEquals('application/javascript', $result->getHeaderLine('Content-Type'));

        $body = $result->getBody()->getContents();
        $this->assertRegExp('/var BaseClass = new Class/', $body);
        $this->assertRegExp('/var Template = new Class/', $body);
    }

    /**
     * test that predefined builds get cached to disk.
     *
     * @return void
     */
    public function testBuildFileIsCached()
    {
        $uri = $this->request->getUri()->withPath('cache_js/libs.js');
        $request = $this->request->withUri($uri);

        $mw = $this->middleware;
        $result = $mw($request, $this->response, $this->next);

        $body = $result->getBody()->getContents();
        $this->assertEquals('application/javascript', $result->getHeaderLine('Content-Type'));
        $this->assertContains('BaseClass', $body);

        $this->assertTrue(file_exists(CACHE . 'asset_compress' . DS . 'libs.js'), 'Cache file was created.');
        unlink(CACHE . 'asset_compress' . DS . 'libs.js');
    }
}
