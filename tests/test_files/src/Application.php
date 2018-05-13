<?php
namespace TestApp;

use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Routing\Middleware\RoutingMiddleware;

class Application extends BaseApplication
{
    public function bootstrap()
    {
        $this->addPlugin('AssetCompress');
    }

    public function routes($routes)
    {
        return $routes;
    }

    public function middleware($middlewareQueue)
    {
        $middlewareQueue
            ->add(ErrorHandlerMiddleware::class)
            ->add(new RoutingMiddleware($this));

        return $middlewareQueue;
    }
}
