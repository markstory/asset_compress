<?php
declare(strict_types=1);

namespace AssetCompress\Middleware;

use AssetCompress\Config\ConfigFinder;
use AssetCompress\Factory;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Exception;
use MiniAsset\AssetConfig;
use MiniAsset\AssetTarget;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR7 Compatible middleware for the new HTTP stack.
 */
class AssetCompressMiddleware implements MiddlewareInterface
{
    /**
     * Object containing configuration settings for asset compressor
     *
     * @var \MiniAsset\AssetConfig
     */
    protected AssetConfig $config;

    /**
     * Constructor
     *
     * @param \MiniAsset\AssetConfig|null $config The config object to use.
     *   If null, \AssetCompress\ConfigFinder::loadAll() will be used.
     */
    public function __construct(?AssetConfig $config = null)
    {
        if ($config === null) {
            $finder = new ConfigFinder();
            $config = $finder->loadAll();
        }
        $this->config = $config;
    }

    /**
     * Callable implementation for the middleware stack.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = $this->config;
        $production = !Configure::read('debug');
        if ($production && !$config->general('alwaysEnableController')) {
            return $handler->handle($request);
        }

        // Make sure the request looks like an asset.
        $targetName = $this->getName($config, $request->getUri()->getPath());
        if (!$targetName) {
            return $handler->handle($request);
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['theme'])) {
            $config->theme($queryParams['theme']);
        }
        $factory = new Factory($config);
        $assets = $factory->assetCollection();
        if (!$assets->contains($targetName)) {
            return $handler->handle($request);
        }
        $build = $assets->get($targetName);

        try {
            $compiler = $factory->cachedCompiler();
            $contents = $compiler->generate($build);
        } catch (Exception $e) {
            throw new NotFoundException($e->getMessage());
        }

        return $this->respond($contents, $build);
    }

    /**
     * Respond with the asset.
     *
     * @param string $contents The asset contents.
     * @param \MiniAsset\AssetTarget $build The build target.
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function respond(string $contents, AssetTarget $build): ResponseInterface
    {
        $response = new Response();

        // Deliver built asset.
        $body = $response->getBody();
        $body->write($contents);
        $body->rewind();

        return $response->withHeader('Content-Type', $this->mapType($build));
    }

    /**
     * Map an extension to a content type
     *
     * @param \MiniAsset\AssetTarget $build The build target.
     * @return string The mapped content type.
     */
    protected function mapType(AssetTarget $build): string
    {
        $ext = $build->ext();
        $types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'svg' => 'image/svg+xml',
        ];

        return $types[$ext] ?? 'application/octet-stream';
    }

    /**
     * Returns the build name for a requested asset
     *
     * @param \MiniAsset\AssetConfig $config The config object to use.
     * @param string $url The url to get an asset name from.
     * @return string|bool false if no build can be parsed from URL
     * with url path otherwise
     */
    protected function getName(AssetConfig $config, string $url): bool|string
    {
        $parts = explode('.', $url);
        if (count($parts) < 2) {
            return false;
        }

        $path = $config->cachePath($parts[count($parts) - 1]);
        if (empty($path)) {
            return false;
        }

        $root = str_replace('\\', '/', WWW_ROOT);
        $path = str_replace('\\', '/', $path);
        $path = str_replace($root, '', $path);
        $path = '/' . ltrim($path, '/');
        if (strpos($url, $path) !== 0) {
            return false;
        }

        return str_replace($path, '', $url);
    }
}
