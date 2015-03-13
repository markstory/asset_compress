<?php
namespace AssetCompress;

use RuntimeException;

/**
 * Parses the ini files AssetCompress uses into arrays that
 * other objects can use.
 */
class AssetConfig
{

    /**
     * Parsed configuration data.
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Filter configuration
     *
     * @var array
     */
    protected $_filters = [];

    /**
     * Target configuration
     *
     * @var array
     */
    protected $_targets = [];

    /**
     * Defaults and conventions for configuration.
     * These defaults are used unless a key is redefined.
     *
     * @var array
     */
    protected static $_defaults = array(
        'js' => array(
            'paths' => array('WEBROOT/js/**')
        ),
        'css' => array(
            'paths' => array('WEBROOT/css/**')
        ),
    );

    /**
     * Names of normal extensions that AssetCompress could
     * handle.
     *
     * @var array
     */
    protected static $_extensionTypes = array(
        'js', 'css', 'png', 'gif', 'jpeg'
    );

    /**
     * A hash of constants that can be expanded when reading ini files.
     *
     * @var array
     */
    public $constantMap = array(
        'APP/' => APP,
        'WEBROOT/' => WWW_ROOT,
        'ROOT' => ROOT
    );

    const FILTERS = 'filters';
    const FILTER_PREFIX = 'filter_';
    const TARGETS = 'targets';
    const GENERAL = 'general';

    /**
     * Constructor, set some initial data for a AssetConfig object.
     *
     * @param array $data Initial data set for the object.
     * @param array $additionalConstants  Additional constants that will be translated
     *    when parsing paths.
     */
    public function __construct(array $data = array(), array $additionalConstants = array())
    {
        $this->_data = $data ?: static::$_defaults;
        $this->constantMap = array_merge($this->constantMap, $additionalConstants);
    }

    /**
     * Factory method
     *
     * @param string $iniFile File path for the ini file to parse.
     * @param array $additionalConstants  Additional constants that will be translated
     *    when parsing paths.
     * @deprecated Use ConfigFinder::loadAll() instead.
     */
    public static function buildFromIniFile($iniFile = null, $constants = array())
    {
        if (empty($iniFile)) {
            $iniFile = CONFIG . 'asset_compress.ini';
        }
        $config = new static([], $constants);
        return $config->load($iniFile);
    }

    /**
     * Load a config file into the current instance.
     *
     * @param string $path The config file to load.
     * @param string $prefix The string to prefix all targets in $path with.
     * @return $this
     */
    public function load($path, $prefix = '')
    {
        $config = $this->_readConfig($path);

        foreach ($config as $section => $values) {
            if (in_array($section, self::$_extensionTypes)) {
                // extension section, merge in the defaults.
                $defaults = $this->get($section);
                if ($defaults) {
                    $values = array_merge($defaults, $values);
                }
                $this->addExtension($section, $values);

            } elseif (strtolower($section) === self::GENERAL) {
                $this->set(self::GENERAL, $values);

            } elseif (strpos($section, self::FILTER_PREFIX) === 0) {
                // filter section.
                $name = str_replace(self::FILTER_PREFIX, '', $section);
                $this->filterConfig($name, $values);

            } else {
                $lastDot = strrpos($section, '.') + 1;
                $extension = substr($section, $lastDot);
                $key = $section;

                // must be a build target.
                $this->addTarget($prefix . $key, $values);
            }
        }

        return $this;
    }

    /**
     * Read the configuration file from disk
     *
     * @param string $filename Name of the inifile to parse
     * @return array Inifile contents
     * @throws RuntimeException
     */
    protected function _readConfig($filename)
    {
        if (empty($filename) || !is_string($filename) || !file_exists($filename)) {
            throw new RuntimeException(sprintf('Configuration file "%s" was not found.', $filename));
        }

        if (function_exists('parse_ini_file')) {
            return parse_ini_file($filename, true);
        } else {
            return parse_ini_string(file_get_contents($filename), true);
        }
    }

    /**
     * Add/Replace an extension configuration.
     *
     * @param string $ext Extension name
     * @param array $config Configuration for the extension
     * @return void
     */
    public function addExtension($ext, array $config)
    {
        $this->_data[$ext] = $this->_parseExtensionDef($config);
    }

    /**
     * Parses paths in an extension definition
     *
     * @param array $data Array of extension information.
     * @return array Array of build extension information with paths replaced.
     */
    protected function _parseExtensionDef($target)
    {
        $paths = array();
        if (!empty($target['paths'])) {
            $paths = array_map(array($this, '_replacePathConstants'), (array)$target['paths']);
        }
        $target['paths'] = $paths;
        if (!empty($target['cachePath'])) {
            $path = $this->_replacePathConstants($target['cachePath']);
            $target['cachePath'] = rtrim($path, '/') . '/';
        }
        return $target;
    }

    /**
     * Replaces the file path constants used in Config files.
     * Will replace APP and WEBROOT
     *
     * @param string $path Path to replace constants on
     * @return string constants replaced
     */
    protected function _replacePathConstants($path)
    {
        return strtr($path, $this->constantMap);
    }

    /**
     * Set values into the config object, You can't modify targets, or filters
     * with this. Use the appropriate methods for those settings.
     *
     * @param string $path The path to set.
     * @param string $value The value to set.
     * @throws RuntimeException
     */
    public function set($path, $value)
    {
        $parts = explode('.', $path);
        if (count($parts) > 2) {
            throw new RuntimeException('Only depth of two can be written to.');
        }
        $stack =& $this->_data;
        while (!empty($parts)) {
            $key = array_shift($parts);
            if (empty($stack[$key]) && !empty($parts)) {
                $stack[$key] = array();
            }
            if (!empty($parts)) {
                $stack =& $stack[$key];
            } else {
                $stack[$key] = $value;
            }
        }
    }

    /**
     * Get values from the config data.
     *
     * @param string $path The path you want.
     */
    public function get($path)
    {
        $parts = explode('.', $path);
        $stack =& $this->_data;
        while (!empty($parts)) {
            $key = array_shift($parts);
            $moreKeys = !empty($parts);
            if (isset($stack[$key]) && $moreKeys) {
                $stack =& $stack[$key];
            } elseif (!$moreKeys) {
                return isset($stack[$key]) ? $stack[$key] : null;
            }
        }
    }

    /**
     * Get/set filters for an extension
     *
     * @param string $ext Name of an extension
     * @param array $filters Filters to replace either the global or per target filters.
     * @return array Filters for extension.
     */
    public function filters($ext, $filters = null)
    {
        if ($filters === null) {
            if (isset($this->_data[$ext][self::FILTERS])) {
                return $this->_data[$ext][self::FILTERS];
            }
            return [];
        }
        $this->_data[$ext][self::FILTERS] = $filters;
    }

    /**
     * Get the filters for a build target.
     *
     * @param string $name The build target to get filters for.
     * @return array
     */
    public function targetFilters($name)
    {
        $ext = $this->getExt($name);
        $filters = [];
        if (isset($this->_data[$ext][self::FILTERS])) {
            $filters = $this->_data[$ext][self::FILTERS];
        }
        if (!empty($this->_targets[$name][self::FILTERS])) {
            $buildFilters = $this->_targets[$name][self::FILTERS];
            $filters = array_merge($filters, $buildFilters);
        }
        return array_unique($filters);
    }

    /**
     * Get configuration for all filters.
     *
     * Useful for building FilterRegistry objects
     *
     * @return array Config data related to all filters.
     */
    public function allFilters()
    {
        $filters = [];
        foreach ($this->extensions() as $ext) {
            if (empty($this->_data[$ext][self::FILTERS])) {
                continue;
            }
            $filters = array_merge($filters, $this->_data[$ext][self::FILTERS]);
        }
        foreach ($this->_targets as $target) {
            if (empty($target[self::FILTERS])) {
                continue;
            }
            $filters = array_merge($filters, $target[self::FILTERS]);
        }
        return array_unique($filters);
    }

    /**
     * Get/Set filter Settings.
     *
     * @param string $filter The filter name
     * @param array $settings The settings to set, leave null to get
     * @return mixed.
     */
    public function filterConfig($filter, $settings = null)
    {
        if ($settings === null) {
            if (is_string($filter)) {
                return isset($this->_filters[$filter]) ? $this->_filters[$filter] : [];
            }
            if (is_array($filter)) {
                $result = [];
                foreach ($filter as $f) {
                    $result[$f] = $this->filterConfig($f);
                }
                return $result;
            }
        }
        $this->_filters[$filter] = $settings;
    }

    /**
     * Get/set the list of files that match the given build file.
     *
     * @param string $target The build file with extension.
     * @return array An array of files for the chosen build.
     */
    public function files($target)
    {
        if (isset($this->_targets[$target]['files'])) {
            return (array)$this->_targets[$target]['files'];
        }
        return [];
    }

    /**
     * Get the extension for a filename.
     *
     * @param string $file
     * @return string
     */
    public function getExt($file)
    {
        return substr($file, strrpos($file, '.') + 1);
    }

    /**
     * Get/set paths for an extension. Setting paths will replace
     * global or per target existing paths. Its only intended for testing.
     *
     * @param string $ext Extension to get paths for.
     * @param string $target A build target. If provided the target's paths (if any) will also be
     *     returned.
     * @param array $paths Paths to replace either the global or per target paths.
     * @return array An array of paths to search for assets on.
     */
    public function paths($ext, $target = null, $paths = null)
    {
        if ($paths === null) {
            if (empty($this->_data[$ext]['paths'])) {
                $paths = array();
            } else {
                $paths = (array)$this->_data[$ext]['paths'];
            }
            if ($target !== null && !empty($this->_targets[$target]['paths'])) {
                $buildPaths = $this->_targets[$target]['paths'];
                $paths = array_merge($paths, $buildPaths);
            }
            return array_unique($paths);
        }

        $paths = array_map(array($this, '_replacePathConstants'), $paths);
        if ($target === null) {
            $this->_data[$ext]['paths'] = $paths;
        } else {
            $this->_targets[$target]['paths'] = $paths;
        }
    }

    /**
     * Accessor for getting the cachePath for a given extension.
     *
     * @param string $ext Extension to get paths for.
     * @param string $path The path to cache files using $ext to.
     */
    public function cachePath($ext, $path = null)
    {
        if ($path === null) {
            if (isset($this->_data[$ext]['cachePath'])) {
                return $this->_data[$ext]['cachePath'];
            }
            return '';
        }
        $path = $this->_replacePathConstants($path);
        $this->_data[$ext]['cachePath'] = rtrim($path, '/') . '/';
    }

    /**
     * Get / set values from the General section. This is preferred
     * to using get()/set() as you don't run the risk of making a
     * mistake in General's casing.
     *
     * @param string $key The key to read/write
     * @param mixed $value The value to set.
     * @return mixed Null when writing. Either a value or null when reading.
     */
    public function general($key, $value = null)
    {
        if ($value === null) {
            return isset($this->_data[self::GENERAL][$key]) ? $this->_data[self::GENERAL][$key] : null;
        }
        $this->_data[self::GENERAL][$key] = $value;
    }

    /**
     * Get the build targets.
     *
     * @return array An array of build targets.
     */
    public function targets()
    {
        if (empty($this->_targets)) {
            return array();
        }
        return array_keys($this->_targets);
    }

    /**
     * Create a new build target.
     *
     * @param string $target Name of the target file. The extension will be inferred based on the last extension.
     * @param array $config Config data for the target. Should contain files, filters and theme key.
     */
    public function addTarget($target, array $config)
    {
        $ext = $this->getExt($target);
        $config += [
            'files' => [],
            'filters' => [],
            'theme' => false,
        ];
        if (!empty($config['paths'])) {
            $config['paths'] = array_map(array($this, '_replacePathConstants'), (array)$config['paths']);
        }
        $this->_targets[$target] = $config;
    }

    /**
     * Set the active theme for building assets.
     *
     * @param string $theme The theme name to set. Null to get
     * @return mixed Either null on set, or theme on get
     */
    public function theme($theme = null)
    {
        if ($theme === null) {
            return isset($this->_data['theme']) ? $this->_data['theme'] : '';
        }
        $this->_data['theme'] = $theme;
    }

    /**
     * Check if a build target is themed.
     *
     * @param string $target A build target.
     * @return boolean
     */
    public function isThemed($target)
    {
        return !empty($this->_targets[$target]['theme']);
    }

    /**
     * Get the list of extensions this config object supports.
     *
     * @return array Extension list.
     */
    public function extensions()
    {
        return ['css', 'js'];
    }
}
