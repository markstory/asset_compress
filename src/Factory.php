<?php
declare(strict_types=1);

namespace AssetCompress;

use Cake\Core\App;
use Cake\Core\Configure;
use MiniAsset\Factory as BaseFactory;
use MiniAsset\Filter\FilterInterface;
use MiniAsset\Output\AssetCacher;
use MiniAsset\Output\AssetWriter;
use MiniAsset\Output\CachedCompiler;
use MiniAsset\Output\Compiler;

/**
 * A factory for various object using a config file.
 *
 * This class can make AssetCollections and FilterCollections based
 * on the configuration object passed to it.
 */
class Factory extends BaseFactory
{
    /**
     * Create an AssetWriter
     *
     * @param string $tmpPath The path to use
     * @return \MiniAsset\Output\AssetWriter
     */
    public function writer(string $tmpPath = TMP): AssetWriter
    {
        return parent::writer($this->config->get('general.timestampPath') ?: $tmpPath);
    }

    /**
     * Create a Caching Compiler
     *
     * @param string $outputDir The directory to output cached files to.
     * @param bool $debug Whether or not to enable debugging mode for the compiler.
     * @return \MiniAsset\Output\CachedCompiler
     */
    public function cachedCompiler(string $outputDir = '', bool $debug = false): CachedCompiler
    {
        $outputDir = $outputDir ?: CACHE . 'asset_compress' . DS;
        $debug = $debug ?: Configure::read('debug');

        return parent::cachedCompiler($outputDir, $debug);
    }

    /**
     * Create an AssetCacher
     *
     * @param string $path The path to read from. Defaults to the application CACHE path.
     * @return \MiniAsset\Output\AssetCacher
     */
    public function cacher(string $path = ''): AssetCacher
    {
        if ($path == '') {
            $path = CACHE . 'asset_compress' . DS;
        }

        return parent::cacher($path);
    }

    /**
     * Create an AssetScanner
     *
     * @param array $paths The paths to read from.
     * @return \AssetCompress\AssetScanner
     */
    public function scanner(array $paths): AssetScanner
    {
        return new AssetScanner($paths, $this->config->theme());
    }

    /**
     * Create a single filter
     *
     * @param string $name The name of the filter to build.
     * @param array $config The configuration for the filter.
     * @return \MiniAsset\Filter\FilterInterface
     */
    protected function buildFilter(string $name, array $config): FilterInterface
    {
        $className = App::className($name, 'Filter');
        if (!class_exists((string)$className)) {
            $className = App::className('AssetCompress.' . $name, 'Filter');
        }
        $className = $className ?: $name;

        return parent::buildFilter($className, $config);
    }

    /**
     * Create an AssetCompiler
     *
     * @param bool $debug Not used - Configure is used instead.
     * @return \MiniAsset\Output\Compiler
     */
    public function compiler(bool $debug = false): Compiler
    {
        return new Compiler($this->filterRegistry(), Configure::read('debug'));
    }
}
