<?php
declare(strict_types=1);

namespace AssetCompress;

use Cake\Core\Plugin as CorePlugin;
use MiniAsset\AssetScanner as BaseScanner;
use RuntimeException;

/**
 * Used for dynamic build files where a set of searchPaths
 * are declared in the config file. This class allows you search through
 * those searchPaths and locate assets.
 */
class AssetScanner extends BaseScanner
{
    /**
     * @const Pattern for various prefixes.
     */
    public const THEME_PATTERN = '/^(?:t|theme)\:/';
    public const PLUGIN_PATTERN = '/^(?:p|plugin)\:(.*)\:(.*)$/';

    /**
     * The current theme if there is one.
     *
     * @var string|null
     */
    protected ?string $theme;

    /**
     * Constructor.
     *
     * @param array $paths The paths to scan.
     * @param string $theme The current theme.
     */
    public function __construct(array $paths, ?string $theme = null)
    {
        $this->theme = $theme;
        parent::__construct($paths);
    }

    /**
     * Resolve a plugin or theme path into the file path without the search paths.
     *
     * @param string $path Path to resolve
     * @return string resolved path
     */
    protected function _expandPrefix(string $path): string
    {
        if (preg_match(self::PLUGIN_PATTERN, $path)) {
            return $this->_expandPlugin($path);
        }
        if ($this->theme && preg_match(self::THEME_PATTERN, $path)) {
            return $this->_expandTheme($path);
        }

        return $path;
    }

    /**
     * Resolve a themed file to its full path. The file will be found on the
     * current theme's path.
     *
     * @param string $file The theme file to find.
     * @return string The expanded path
     */
    protected function _expandTheme(string $file): string
    {
        $file = preg_replace(self::THEME_PATTERN, '', $file);

        return CorePlugin::path($this->theme) . 'webroot' . DS . $file;
    }

    /**
     * Resolve a plugin file to its full path.
     *
     * @param string $file The theme file to find.
     * @throws \RuntimeException when plugins are missing.
     * @return string The expanded path
     */
    protected function _expandPlugin(string $file): string
    {
        preg_match(self::PLUGIN_PATTERN, $file, $matches);
        if (empty($matches[1]) || empty($matches[2])) {
            throw new RuntimeException('Missing required parameters');
        }
        if (!CorePlugin::isLoaded($matches[1])) {
            throw new RuntimeException($matches[1] . ' is not a loaded plugin.');
        }
        $path = CorePlugin::path($matches[1]);

        return $path . 'webroot' . DS . $matches[2];
    }
}
