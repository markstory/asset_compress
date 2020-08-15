<?php
declare(strict_types=1);

namespace AssetCompress\Test\TestCase\Middleware;

use AssetCompress\Middleware\AssetCompressMiddleware;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use MiniAsset\AssetConfig;

class AssetCompressMiddlewareTest extends TestCase
{
    protected $nextInvoked = false;

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->testConfig = APP . 'config' . DS . 'integration.ini';
        $this->nextInvoked = false;

        $this->loadPlugins(['TestAssetIni']);

        $config = new AssetConfig([]);
        $config->load($this->testConfig);
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.ini', 'TestAssetIni.');
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.local.ini', 'TestAssetIni.');

        $this->middleware = new AssetCompressMiddleware($config);
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->handler = new RequestHandlerStub(function () {
            $this->nextInvoked = true;

            return new Response();
        });
    }

    /**
     * teardown method.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
    }

    /**
     * test building assets interactively
     *
     * @return void
     */
    public function testBuildFile()
    {
        $uri = $this->request->getUri()->withPath('/cache_js/libs.js');
        $request = $this->request->withUri($uri);

        $result = $this->middleware->process($request, $this->handler);

        $body = $result->getBody()->getContents();
        $this->assertStringContainsString('var BaseClass = new Class', $body);
        $this->assertStringContainsString('var Template = new Class', $body);
    }

    public function contentTypesProvider()
    {
        return [
            ['/cache_js/libs.js',      'application/javascript'],
            ['/cache_css/all.css',     'text/css'],
            ['/cache_svg/foo.bar.svg', 'image/svg+xml'],
        ];
    }

    /**
     * test returned content types
     *
     * @dataProvider contentTypesProvider
     * @return void
     */
    public function testBuildFileContentTypes($path, $expected)
    {
        $uri = $this->request->getUri()->withPath($path);
        $request = $this->request->withUri($uri);

        $result = $this->middleware->process($request, $this->handler);

        $this->assertEquals($expected, $result->getHeaderLine('Content-Type'));
    }

    /**
     * test building plugin assets.
     *
     * @return void
     */
    public function testPluginIniBuildFile()
    {
        $this->loadPlugins(['TestAssetIni']);

        $uri = $this->request->getUri()->withPath('/cache_js/TestAssetIni.libs.js');
        $request = $this->request->withUri($uri);

        $result = $this->middleware->process($request, $this->handler);

        $body = $result->getBody()->getContents();
        $this->assertStringContainsString('var BaseClass = new Class', $body);
        $this->assertStringContainsString('var Template = new Class', $body);
    }

    /**
     * test that predefined builds get cached to disk.
     *
     * @return void
     */
    public function testBuildFileIsCached()
    {
        $uri = $this->request->getUri()->withPath('/cache_js/libs.js');
        $request = $this->request->withUri($uri);

        $result = $this->middleware->process($request, $this->handler);

        $body = $result->getBody()->getContents();
        $this->assertStringContainsString('BaseClass', $body);

        $this->assertTrue(file_exists(CACHE . 'asset_compress' . DS . 'libs.js'), 'Cache file was created.');
        unlink(CACHE . 'asset_compress' . DS . 'libs.js');
    }

    public function testProductionMode()
    {
        Configure::write('debug', false);
        $uri = $this->request->getUri()->withPath('/cache_js/libs.js');
        $request = $this->request->withUri($uri);

        $this->middleware->process($request, $this->handler);
        $this->assertTrue($this->nextInvoked);
    }

    public function testBuildThemedAsset()
    {
        $this->loadPlugins(['Blue']);

        $configFile = APP . 'config' . DS . 'themed.ini';
        $map = [
            'WEBROOT' => WWW_ROOT,
            'TEST_FILES' => APP,
        ];
        $config = new AssetConfig([], $map);
        $config->load($configFile);
        $this->middleware = new AssetCompressMiddleware($config);

        $uri = $this->request->getUri()->withPath('/cache_css/themed.css');
        $request = $this->request->withUri($uri)
            ->withQueryParams(['theme' => 'Blue']);

        $result = $this->middleware->process($request, $this->handler);

        $body = $result->getBody()->getContents();
        $this->assertStringContainsString('color: blue', $body);
    }

    public function testDelegateOnUndefinedAsset()
    {
        $uri = $this->request->getUri()->withPath('/cache_js/derpy.js');
        $request = $this->request->withUri($uri);

        $this->middleware->process($request, $this->handler);
        $this->assertTrue($this->nextInvoked);
    }
}
