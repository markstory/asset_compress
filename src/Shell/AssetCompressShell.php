<?php
namespace AssetCompress\Shell;

use AssetCompress\AssetCache;
use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use AssetCompress\Factory;
use Cake\Console\Shell;
use Cake\Utility\Folder;
use DirectoryIterator;

/**
 * Asset Compress Shell
 *
 * Assists in clearing and creating the build files this plugin makes.
 *
 */
class AssetCompressShell extends Shell
{

    public $tasks = array('AssetCompress.AssetBuild');

    protected $config;
    protected $factory;

    /**
     * Create the configuration object used in other classes.
     *
     */
    public function startup()
    {
        parent::startup();

        AssetConfig::clearAllCachedKeys();
        $this->setConfig(AssetConfig::buildFromIniFile($this->params['config']));
        $this->out();
    }

    /**
     * Set the config object.
     *
     * @var \AssetCompress\AssetConfig $config The config instance.
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
        $this->factory = new Factory($config);
        $this->AssetBuild->setConfig($config);
    }

    /**
     * Builds all the files defined in the build file.
     *
     * @return void
     */
    public function build()
    {
        $this->AssetBuild->setConfig($this->config);
        $this->AssetBuild->build();
    }

    /**
     * Clears the build directories for both CSS and JS
     *
     * @return void
     */
    public function clear()
    {
        $this->clear_build_ts();

        $this->_io->verbose('Clearing build files:');
        $this->_clearBuilds();

        $this->_io->verbose('');
        $this->out('<success>Complete</success>');
    }

    /**
     * Clears out all the cache keys associated with asset_compress.
     *
     * Note: method really does nothing here because keys are cleared in startup.
     * This method exists for times when you just want to clear the cache keys
     * associated with asset_compress
     */
    public function clear_cache()
    {
        $this->out('Clearing all cache keys:');
        $this->hr();
    }

    /**
     * Clears the build timestamp. Try to clear it out even if they do not have ts file enabled in
     * the INI.
     *
     * build timestamp file is only created when build() is run from this shell
     */
    public function clear_build_ts()
    {
        $this->_io->verbose('Clearing build timestamp.');
        AssetConfig::clearBuildTimeStamp();
    }

    /**
     * clear the builds for a specific extension.
     *
     * @return void
     */
    protected function _clearBuilds()
    {
        $themes = (array)$this->config->general('themes');
        if ($themes) {
            $this->config->theme($themes[0]);
        }
        $assets = $this->factory->assetCollection();
        if (count($assets) === 0) {
            $this->err('No build targets defined, skipping');
            return;
        }
        $targets = array_map(function ($target) {
            return $target->name();
        }, iterator_to_array($assets));


        $this->_clearPath(CACHE . 'asset_compress' .DS, $themes, $targets);
        $this->_clearPath($this->config->cachePath('js'), $themes, $targets);
        $this->_clearPath($this->config->cachePath('css'), $themes, $targets);
    }

    /**
     * Clear a path of build targets.
     *
     * @param string $path The root path to clear.
     * @param array $themes The themes to clear.
     * @param array $targets The build targets to clear.
     * @return void
     */
    protected function _clearPath($path, $themes, $targets)
    {
        if (!file_exists($path)) {
            return;
        }

        $dir = new DirectoryIterator($path);
        foreach ($dir as $file) {
            $name = $base = $file->getFilename();
            if (in_array($name, array('.', '..'))) {
                continue;
            }
            // timestampped files.
            if (preg_match('/^(.*)\.v\d+(\.[a-z]+)$/', $name, $matches)) {
                $base = $matches[1] . $matches[2];
            }
            // themed files
            foreach ($themes as $theme) {
                if (strpos($base, $theme) === 0 && strpos($base, '-') !== false) {
                    list($themePrefix, $base) = explode('-', $base);
                }
            }
            if (in_array($base, $targets)) {
                $this->_io->verbose(' - Deleting ' . $path . $name);
                unlink($path . $name);
                continue;
            }
        }
    }

    /**
     * get the option parser.
     *
     * @return void
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        return $parser->description(array(
            'Asset Compress Shell',
            '',
            'Builds and clears assets defined in your asset_compress.ini',
            'file and in your view files.'
        ))->addSubcommand('clear', array(
            'help' => 'Clears all builds defined in the ini file.'
        ))->addSubcommand('build', array(
            'help' => 'Generate all builds defined in the ini files.'
        ))->addOption('config', array(
            'help' => 'Choose the config file to use.',
            'short' => 'c',
            'default' => CONFIG . 'asset_compress.ini'
        ))->addOption('force', array(
            'help' => 'Force assets to rebuild. Ignores timestamp rules.',
            'short' => 'f',
            'boolean' => true
        ));
    }
}
