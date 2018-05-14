<?php
namespace AssetCompress;

use AssetCompress\Middleware\AssetCompressMiddleware;
use Cake\Core\BasePlugin;
use Cake\Error\Middleware\ErrorHandlerMiddleware;

/**
 * Plugin class defining framework hooks.
 */
class Plugin extends BasePlugin
{
    /**
     * Add middleware
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The queue
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware($middlewareQueue)
    {
        $middleware = new AssetCompressMiddleware();
        $middlewareQueue->insertAfter(ErrorHandlerMiddleware::class, $middleware);

        return $middlewareQueue;
    }
}
