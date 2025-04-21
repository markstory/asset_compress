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
     * @param string|null $path The configuration file path to start loading from.
     * @param bool $skipPlugins Whether to skip config files from plugins. Default `false`.
     * @param bool $skipLocal Whether to skip config *local* files. Default `false`.
     * @return \MiniAsset\AssetConfig The completed configuration object.
     */
    public function loadAll(?string $path = null, bool $skipPlugins = false, bool $skipLocal = false): AssetConfig
    {
        if (!$path) {
            $path = CONFIG . 'asset_compress.ini';
        }
        $config = new AssetConfig([], [
            'WEBROOT' => WWW_ROOT,
        ]);
        $this->_load($config, $path, '', $skipLocal);

        if ($skipPlugins) {
            return $config;
        }

        $plugins = Plugin::loaded();
        foreach ($plugins as $plugin) {
            $pluginConfig = Plugin::path($plugin) . 'config' . DS . 'asset_compress.ini';
            $this->_load($config, $pluginConfig, $plugin . '.', $skipLocal);
        }

        return $config;
    }

    /**
     * Load a config file and its `.local` file if it exists.
     *
     * @param \MiniAsset\AssetConfig $config The config object to update.
     * @param string $path The config file to load.
     * @param string $prefix The prefix to use.
     * @param bool $skipLocal Skip *.local.ini file lookup
     * @return void
     */
    protected function _load(AssetConfig $config, string $path, string $prefix = '', bool $skipLocal = false): void
    {
        if (file_exists($path)) {
            $config->load($path, $prefix);
        }

        if ($skipLocal) {
            return;
        }

        $localConfig = (string)preg_replace('/(.*)\.ini$/', '$1.local.ini', $path);
        if (file_exists($localConfig)) {
            $config->load($localConfig, $prefix);
        }
    }
}
