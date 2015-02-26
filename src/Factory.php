<?php
namespace AssetCompress;

use AssetCompress\AssetConfig;
use AssetCompress\AssetCollection;
use AssetCompress\AssetTarget;
use AssetCompress\Filter\FilterRegistry;
use AssetCompress\File\Remote;
use AssetCompress\File\Local;
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
    protected $config;

    public function __construct(AssetConfig $config)
    {
        $this->config = $config;
    }

    public function assetCollection()
    {
        $assets = [];
        foreach ($this->config->extensions() as $ext) {
            foreach ($this->config->targets($ext) as $targetName) {
                $assets[] = $this->buildTarget($targetName);
            }
        }
        return new AssetCollection($assets);
    }

    protected function buildTarget($name)
    {
        $ext = $this->config->getExt($name);

        $filters = $this->config->filters($ext, $name);
        $paths = $this->config->paths($ext, $name);
        $themed = $this->config->isThemed($name);

        $files = [];
        $scanner = new AssetScanner($paths, $this->config->theme());
        foreach ($this->config->files($name) as $file) {
            if (preg_match('#^https?://#', $file)) {
                $files[] = new Remote($file);
            } else {
                $path = $scanner->find($file);
                if ($path === false) {
                    throw new RuntimeException("Could not locate $file in any configured path.");
                }
                $files[] = new Local($path);
            }
        }
        return new AssetTarget($name, $files, $filters, $paths, $themed);
    }

    public function filterRegistry()
    {
        $filters = [];
        foreach ($this->config->allFilters() as $name) {
            $filters[$name] = $this->buildFilter($name, $this->config->filterConfig($name));
        }
        return new FilterRegistry($filters);
    }

    protected function buildFilter($name, $config)
    {
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
