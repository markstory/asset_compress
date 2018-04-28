<?php
namespace AssetCompress\Shell;

use AssetCompress\Config\ConfigFinder;
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

    /**
     * Tasks used by this shell.
     *
     * @var array
     */
    public $tasks = ['AssetCompress.AssetBuild'];

    /**
     * Config instance
     *
     * @var \MiniAsset\AssetConfig
     */
    protected $config;

    /**
     * Factory instance.
     *
     * @var \AssetCompress\Factory
     */
    protected $factory;

    /**
     * Create the configuration object used in other classes.
     *
     * @return void
     */
    public function startup()
    {
        parent::startup();
        $configFinder = new ConfigFinder();
        $this->setConfig($configFinder->loadAll());
        $this->out();
    }

    /**
     * Set the config object.
     *
     * @param \MiniAsset\AssetConfig $config The config instance.
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
        $this->clearBuildTs();

        $this->_io->verbose('Clearing build files:');
        $this->_clearBuilds();

        $this->_io->verbose('');
        $this->out('<success>Complete</success>');
    }

    /**
     * Clears the build timestamp. Try to clear it out even if they do not have ts file enabled in
     * the INI.
     *
     * Build timestamp file is only created when build() is run from this shell
     *
     * @return void
     */
    public function clearBuildTs()
    {
        $this->_io->verbose('Clearing build timestamp.');
        $writer = $this->factory->writer();
        $writer->clearTimestamps();
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

        $this->_clearPath(CACHE . 'asset_compress' . DS, $themes, $targets);
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
            if (in_array($name, ['.', '..'])) {
                continue;
            }
            // timestamped files.
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
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        return $parser->setDescription([
            'Asset Compress Shell',
            '',
            'Builds and clears assets defined in your asset_compress.ini',
            'file and in your view files.'
        ])->addSubcommand('clear', [
            'help' => 'Clears all builds defined in the ini file.'
        ])->addSubcommand('build', [
            'help' => 'Generate all builds defined in the ini files.'
        ])->addOption('force', [
            'help' => 'Force assets to rebuild. Ignores timestamp rules.',
            'short' => 'f',
            'boolean' => true
        ]);
    }
}
