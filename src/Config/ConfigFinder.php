<?php
namespace AssetCompress\Config;

use Cake\Core\Plugin;
use MiniAsset\AssetConfig;

/**
 * Find and create a configuration object by
 * looking in all the places a CakePHP application could
 * have config files for AssetCompress.
 *
 * The app and each plugin could have config files, in addition for
 * each config file that is found, also look for a `.local` file for
 * additional overrides.
 */
class ConfigFinder
{

    /**
     * Load all configuration files in the application.
     *
     * Loads:
     *
     * - The app config (asset_compress.ini)
     * - The asset_compress.ini file in each plugin.
     *
     * In addition for each file found the `asset_compress.local.ini`
     * will be loaded if it is present.
     *
     * @param string $path The configuration file path to start loading from.
     * @return \MiniAsset\AssetConfig The completed configuration object.
     */
    public function loadAll($path = null)
    {
        if (!$path) {
            $path = CONFIG . 'asset_compress.ini';
        }
        $config = new AssetConfig([], [
            'WEBROOT' => WWW_ROOT,
        ]);
        $this->_load($config, $path);

        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            $pluginConfig = Plugin::path($plugin) . 'config' . DS . 'asset_compress.ini';
            $this->_load($config, $pluginConfig, $plugin . '.');
        }

        return $config;
    }

    /**
     * Load a config file and its `.local` file if it exists.
     *
     * @param \MiniAsset\AssetConfig $config The config object to update.
     * @param string $path The config file to load.
     * @param string $prefix The prefix to use.
     * @return void
     */
    protected function _load($config, $path, $prefix = '')
    {
        if (file_exists($path)) {
            $config->load($path, $prefix);
        }

        $localConfig = preg_replace('/(.*)\.ini$/', '$1.local.ini', $path);
        if (file_exists($localConfig)) {
            $config->load($localConfig, $prefix);
        }
    }
}
