<?php
declare(strict_types=1);

namespace AssetCompress;

use AssetCompress\Command\BuildCommand;
use AssetCompress\Command\ClearCommand;
use AssetCompress\Middleware\AssetCompressMiddleware;
use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\MiddlewareQueue;

/**
 * Plugin class defining framework hooks.
 */
class AssetCompressPlugin extends BasePlugin
{
    protected bool $bootstrapEnabled = false;
    protected bool $routesEnabled = false;

    /**
     * Add middleware
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The queue
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middleware = new AssetCompressMiddleware();
        $middlewareQueue->insertAfter(ErrorHandlerMiddleware::class, $middleware);

        return $middlewareQueue;
    }

    /**
     * Console hook
     *
     * @param \Cake\Console\CommandCollection $commands The command collection
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('asset_compress build', BuildCommand::class);
        $commands->add('asset_compress clear', ClearCommand::class);

        return $commands;
    }
}
