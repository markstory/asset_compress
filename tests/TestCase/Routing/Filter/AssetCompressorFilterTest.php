<?php
namespace AssetCompress\Test\TestCase\Routing\Filter;

use AssetCompress\Routing\Filter\AssetCompressorFilter;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use MiniAsset\AssetConfig;

class AssetsCompressorFilterTest extends TestCase
{

    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->level = error_reporting();
        error_reporting(E_ALL ^ E_USER_DEPRECATED);

        $this->testConfig = APP . 'config' . DS . 'integration.ini';

        $map = [
            'WEBROOT' => WWW_ROOT,
            'TEST_FILES' => APP
        ];
        Plugin::load('TestAssetIni');

        $config = new AssetConfig([], $map);
        $config->load($this->testConfig);
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.ini', 'TestAssetIni.');
        $config->load(APP . 'Plugin/TestAssetIni/config/asset_compress.local.ini', 'TestAssetIni.');

        $this->Compressor = $this->getMockBuilder('AssetCompress\Routing\Filter\AssetCompressorFilter')
            ->setMethods(['_getConfig'])
            ->getMock();
        $this->Compressor->expects($this->atLeastOnce())
            ->method('_getConfig')
            ->will($this->returnValue($config));

        $this->request = new ServerRequest();
        $this->response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['checkNotModified', 'type', 'send'])
            ->getMock();
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
        error_reporting($this->level);
    }

    /**
     * test building assets interactively
     *
     * @return void
     */
    public function testBuildFile()
    {
        $this->response
            ->expects($this->once())
            ->method('type')
            ->with($this->equalTo('js'));

        $this->request->url = 'cache_js/libs.js';
        $data = ['request' => $this->request, 'response' => $this->response];
        $event = new Event('Dispatcher.beforeDispatch', $this, $data);
        $this->assertSame($this->response, $this->Compressor->beforeDispatch($event));

        $this->assertRegExp('/var BaseClass = new Class/', $this->response->body());
        $this->assertRegExp('/var Template = new Class/', $this->response->body());
        $this->assertTrue($event->isStopped());
    }

    /**
     * test building plugin assets.
     *
     * @return void
     */
    public function testPluginIniBuildFile()
    {
        Plugin::load('TestAssetIni');

        $this->response
            ->expects($this->once())->method('type')
            ->with($this->equalTo('js'));

        $this->request->url = 'cache_js/TestAssetIni.libs.js';
        $data = ['request' => $this->request, 'response' => $this->response];
        $event = new Event('Dispatcher.beforeDispatch', $this, $data);
        $this->assertSame($this->response, $this->Compressor->beforeDispatch($event));

        $this->assertRegExp('/var BaseClass = new Class/', $this->response->body());
        $this->assertRegExp('/var Template = new Class/', $this->response->body());
        $this->assertTrue($event->isStopped());
    }

    /**
     * test that predefined builds get cached to disk.
     *
     * @return void
     */
    public function testBuildFileIsCached()
    {
        $this->request->url = 'cache_js/libs.js';
        $data = ['request' => $this->request, 'response' => $this->response];
        $event = new Event('Dispatcher.beforeDispatch', $this, $data);
        $this->assertSame($this->response, $this->Compressor->beforeDispatch($event));

        $this->assertContains('BaseClass', $this->response->body());
        $this->assertTrue($event->isStopped());
        $this->assertTrue(file_exists(CACHE . 'asset_compress' . DS . 'libs.js'), 'Cache file was created.');
        unlink(CACHE . 'asset_compress' . DS . 'libs.js');
    }
}
