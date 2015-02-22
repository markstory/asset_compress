<?php
namespace AssetCompress;

use AssetCompress\AssetConfig;
use AssetCompress\Filter\FilterRegistry;
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
            throw new RuntimeException(sprintf('Cannot not load filter "%s".', $name));
        }
        $filter = new $className();
        $filter->settings($config);
        return $filter;
    }
}
