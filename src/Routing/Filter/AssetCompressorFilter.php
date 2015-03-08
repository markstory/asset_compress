<?php
namespace AssetCompress\Routing\Filter;

use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use AssetCompress\AssetWriter;
use AssetCompress\Factory;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Routing\DispatcherFilter;
use Cake\Filesystem\Folder;
use RuntimeException;

class AssetCompressorFilter extends DispatcherFilter
{

    /**
     * Filter priority, we need it to run before router
     *
     * @var integer
     */
    public $priority = 9;

    /**
     * Object containing configuration settings for asset compressor
     *
     * @var AssetConfig
     */
    protected $config;

    /**
     * Checks if request is for a compiled asset, otherwise skip any operation
     *
     * @param Event $event containing the request and response object
     * @throws NotFoundException
     * @return Response if the client is requesting a recognized asset, null otherwise
     */
    public function beforeDispatch(Event $event)
    {
        $request = $event->data['request'];
        $response = $event->data['response'];
        $config = $this->_getConfig();
        $production = !Configure::read('debug');
        if ($production && !$config->general('alwaysEnableController')) {
            return;
        }

        // Make sure the request looks like an asset.
        $targetName = $this->getName($config, $request->url);
        if (!$targetName) {
            return;
        }

        // Use the CACHE dir for dev builds.
        // This is to avoid permissions issues with the configured paths.
        $cachePath = CACHE . 'asset_compress' . DS;
        $this->ensureDir($cachePath);

        // Reset the cache path for dev builds.
        $ext = $config->getExt($targetName);
        $config->cachePath($ext, $cachePath);
        $config->set("$ext.timestamp", false);

        if (isset($request->query['theme'])) {
            $config->theme($request->query['theme']);
        }
        $factory = new Factory($config);
        $assets = $factory->assetCollection();
        if (!$assets->contains($targetName)) {
            return;
        }
        $build = $assets->get($targetName);

        try {
            $compiler = $factory->compiler();
            $cache = $factory->writer();
            if ($cache->isFresh($build)) {
                $contents = file_get_contents($build->path());
            } else {
                $contents = $compiler->generate($build);
                $cache->write($build, $contents);
            }
        } catch (Exception $e) {
            throw new NotFoundException($e->getMessage());
        }

        $response->type($build->ext());
        $response->body($contents);
        $event->stopPropagation();
        return $response;
    }

    /**
     * Returns the build name for a requested asset
     *
     * @return boolean|string false if no build can be parsed from URL
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

        $path = str_replace(WWW_ROOT, '', $path);
        if (strpos($url, $path) !== 0) {
            return false;
        }
        return str_replace($path, '', $url);
    }

    /**
     * Config setter, used for testing the filter.
     */
    protected function _getConfig()
    {
        if (empty($this->config)) {
            $this->config = AssetConfig::buildFromIniFile();
        }
        return $this->config;
    }

    protected function ensureDir($path)
    {
        $folder = new Folder($path, true);
        $folder->chmod($path, 0777);
    }
}
