<?php
namespace AssetCompress\Middleware;

use AssetCompress\Config\ConfigFinder;
use AssetCompress\Factory;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use MiniAsset\AssetConfig;

/**
 * PSR7 Compatible middleware for the new HTTP stack.
 */
class AssetCompressMiddleware
{
    /**
     * Object containing configuration settings for asset compressor
     *
     * @var \MiniAsset\AssetConfig
     */
    protected $config;

    /**
     * Constructor
     *
     * @param \MiniAsset\AssetConfig|null $config The config object to use.
     *   If null, \AssetCompress\ConfigFinder::loadAll() will be used.
     */
    public function __construct(AssetConfig $config = null)
    {
        if ($config === null) {
            $finder = new ConfigFinder();
            $config = $finder->loadAll();
        }
        $this->config = $config;
    }

    /**
     * Get an asset or delegate to the next middleware
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke($request, $response, $next)
    {
        $config = $this->config;
        $production = !Configure::read('debug');
        if ($production && !$config->general('alwaysEnableController')) {
            return $next($request, $response);
        }

        // Make sure the request looks like an asset.
        $targetName = $this->getName($config, $request->getUri()->getPath());
        if (!$targetName) {
            return $next($request, $response);
        }

        $queryParams = $request->getQueryParams();
        if (isset($queryParams['theme'])) {
            $config->theme($queryParams['theme']);
        }
        $factory = new Factory($config);
        $assets = $factory->assetCollection();
        if (!$assets->contains($targetName)) {
            return $next($request, $response);
        }
        $build = $assets->get($targetName);

        try {
            $compiler = $factory->cachedCompiler();
            $contents = $compiler->generate($build);
        } catch (Exception $e) {
            throw new NotFoundException($e->getMessage());
        }

        return $this->respond($response, $contents, $build);
    }

    /**
     * Respond with the asset.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The response to augment
     * @param string $contents The asset contents.
     * @param \MiniAsset\AssetTarget $build The build target.
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function respond($response, $contents, $build)
    {
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
    protected function mapType($build)
    {
        $ext = $build->ext();
        $types = [
            'css' => 'text/css',
            'js' => 'application/javascript'
        ];

        return isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
    }

    /**
     * Returns the build name for a requested asset
     *
     * @param \MiniAsset\AssetConfig $config The config object to use.
     * @param string $url The url to get an asset name from.
     * @return bool|string false if no build can be parsed from URL
     * with url path otherwise
     */
    protected function getName($config, $url)
    {
        $parts = explode('.', $url);
        if (count($parts) < 2) {
            return false;
        }

        $path = $config->cachePath($parts[(count($parts) - 1)]);
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
