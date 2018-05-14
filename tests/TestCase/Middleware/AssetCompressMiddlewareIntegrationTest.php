<?php
namespace AssetCompress\Test\TestCase\Middleware;

use AssetCompress\Middleware\AssetCompressMiddleware;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\IntegrationTestCase;
use MiniAsset\AssetConfig;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class AssetsCompressMiddlewareIntegrationTest extends IntegrationTestCase
{
    /**
     * Setup method
     *
     * @return void
     */
    public function setUp()
    {
        Configure::write('App.namespace', 'TestApp');

        parent::setUp();
        $this->useHttpServer(true);
    }

    public function testInvokeSuccess()
    {
        $this->get('/cache_js/libs.js');
        $this->assertResponseOk();
        $this->assertHeader('content-type', 'application/javascript');
        $this->assertResponseContains('var Template = new Class({');
    }

    public function testInvokeNotFound()
    {
        $this->get('/cache_js/nope.js');
        $this->assertResponseCode(404);
        $this->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
