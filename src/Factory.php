<?php
namespace AssetCompress;

use AssetCompress\AssetCollection;
use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use AssetCompress\File\Remote;
use AssetCompress\Filter\FilterRegistry;
use AssetCompress\Output\AssetCacher;
use AssetCompress\Output\AssetWriter;
use Cake\Core\App;
use RuntimeException;

/**
 * A factory for various object using a config file.
 *
 * This class can make AssetCollections and FilterCollections based
 * on the configuration object passed to it.
 */
class Factory
{
    /**
     * The config instance to make objects based on.
     *
     * @var AssetCompress\AssetConfig
     */
    protected $config;

    /**
     * Constructor
     *
     * @param AssetCompress\AssetConfig $config
     */
    public function __construct(AssetConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Create an AssetCompiler
     *
     * @return AssetCompress\AssetCompiler
     */
    public function compiler()
    {
        return new AssetCompiler($this->filterRegistry());
    }

    /**
     * Create an AssetWriter
     *
     * @return AssetCompress\AssetWriter
     */
    public function writer()
    {
        $timestamp = [
            'js' => $this->config->get('js.timestamp'),
            'css' => $this->config->get('css.timestamp'),
        ];
        return new AssetWriter($timestamp, TMP, $this->config->theme());
    }

    /**
     * Create an AssetCacher
     *
     * @return AssetCompress\AssetCacher
     */
    public function cacher()
    {
        return new AssetCacher(
            CACHE . 'asset_compress' . DS,
            $this->config->theme()
        );
    }

    /**
     * Create an AssetCollection with all the configured assets.
     *
     * @return AssetCompress\AssetCollection
     */
    public function assetCollection()
    {
        $assets = [];
        foreach ($this->config->extensions() as $ext) {
            $assets = array_merge($assets, $this->config->targets($ext));
        }
        return new AssetCollection($assets, $this);
    }

    /**
     * Create a single build target
     *
     * @param string $name The name of the target to build
     * @return AssetCompress\AssetTarget
     */
    public function target($name)
    {
        $ext = $this->config->getExt($name);

        $paths = $this->config->paths($ext, $name);
        $themed = $this->config->isThemed($name);
        $filters = $this->config->targetFilters($name);
        $target = $this->config->cachePath($ext) . $name;

        $files = [];
        $scanner = new AssetScanner($paths, $this->config->theme());
        foreach ($this->config->files($name) as $file) {
            if (preg_match('#^https?://#', $file)) {
                $files[] = new Remote($file);
            } else {
                $path = $scanner->find($file);
                if ($path === false) {
                    throw new RuntimeException("Could not locate $file for $name in any configured path.");
                }
                $files[] = new Local($path);
            }
        }
        return new AssetTarget($target, $files, $filters, $paths, $themed);
    }

    /**
     * Create a filter registry containing all the configured filters.
     *
     * @return AssetCompress\Filter\FilterRegistry
     */
    public function filterRegistry()
    {
        $filters = [];
        foreach ($this->config->allFilters() as $name) {
            $filters[$name] = $this->buildFilter($name, $this->config->filterConfig($name));
        }
        return new FilterRegistry($filters);
    }

    /**
     * Create a single filter
     *
     * @param string $name The name of the filter to build.
     * @param array $config The configuration for the filter.
     * @return AssetCompress\Filter\AssetFilterInterface
     */
    protected function buildFilter($name, $config)
    {
        // TODO remove reliance on App so the code can be extracted.
        $className = App::className($name, 'Filter');
        if (!class_exists($className)) {
            $className = App::className('AssetCompress.' . $name, 'Filter');
        }
        if (!class_exists($className)) {
            throw new RuntimeException(sprintf('Cannot load filter "%s".', $name));
        }
        $filter = new $className();
        $filter->settings($config);
        return $filter;
    }
}
