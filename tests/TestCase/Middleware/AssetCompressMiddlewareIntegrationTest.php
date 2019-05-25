<?php
declare(strict_types=1);

namespace AssetCompress\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestCase;

class AssetCompressMiddlewareIntegrationTest extends IntegrationTestCase
{
    /**
     * Setup method
     *
     * @return void
     */
    public function setUp(): void
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
