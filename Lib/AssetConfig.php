<?php
/**
 * Parses the ini files AssetCompress uses into arrays that
 * other objects can use.
 */
class AssetConfig {

/**
 * Parsed configuration data.
 *
 * @var array
 */
	protected $_data = array();

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
 * The timestamp that configuration changed.
 *
 * @var integer
 */
	protected $_modifiedTime;

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
	const CACHE_ASSET_CONFIG_KEY = 'cakephp_asset_config_parsed';
	const CACHE_BUILD_TIME_KEY = 'cakephp_asset_config_ts';
	const CACHE_CONFIG = 'asset_compress';
	const BUILD_TIME_FILE = 'asset_compress_build_time';
	const GENERAL = 'general';

/**
 * Constructor, set some initial data for a AssetConfig object.
 *
 * @param array $data Initial data set for the object.
 * @param array $additionalConstants  Additional constants that will be translated
 *    when parsing paths.
 * @param integer $modifiedTime The time configuration data changed.
 */
	public function __construct(array $data = array(), array $additionalConstants = array(), $modifiedTime = null) {
		$this->_data = $data;
		$this->constantMap = array_merge($this->constantMap, $additionalConstants);
		if (!$modifiedTime) {
			$modifiedTime = time();
		}
		$this->_modifiedTime = $modifiedTime;
	}

/**
 * Constructor
 *
 * @param string $iniFile File path for the ini file to parse.
 * @param array $additionalConstants  Additional constants that will be translated
 *    when parsing paths.
 */
	public static function buildFromIniFile($iniFile = null, $constants = array()) {
		if (empty($iniFile)) {
			$iniFile = APP . 'Config' . DS . 'asset_compress.ini';
		}

		// If the AssetConfig is in cache, means that user had General.cacheConfig in their ini.
		if ($parsedConfig = Cache::read(self::CACHE_ASSET_CONFIG_KEY, self::CACHE_CONFIG)) {
			return $parsedConfig;
		}

		return self::_parseConfig($iniFile, $constants);
	}

/**
 * Clear the build timestamp file and the associated cache entry
 */
	public static function clearBuildTimeStamp() {
		// @codingStandardsIgnoreStart
		@unlink(TMP . self::BUILD_TIME_FILE);
		// @codingStandardsIgnoreEnd
		self::clearCachedBuildTime();
	}

/**
 * Clear the cached key for the build timestamp
 *
 * @return void
 */
	public static function clearCachedBuildTime() {
		Cache::delete(self::CACHE_BUILD_TIME_KEY, self::CACHE_CONFIG);
	}

/**
 * Clear the stored config object from cache
 *
 * @return void
 */
	public static function clearCachedAssetConfig() {
		Cache::delete(self::CACHE_ASSET_CONFIG_KEY, self::CACHE_CONFIG);
	}

/**
 * Clear all the cached keys associated with AssetConfig
 */
	public static function clearAllCachedKeys() {
		self::clearCachedBuildTime();
		self::clearCachedAssetConfig();
	}

/**
 * Read the configuration file from disk
 *
 * @param string $filename Name of the inifile to parse
 * @return array Inifile contents
 * @throws RuntimeException
 */
	protected static function _readConfig($filename) {
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
 * Transforms the config data into a more structured form
 *
 * @param array $contents Contents to build a config object from.
 * @param array $constants Array of constants that will be mapped.
 * @param integer $modifiedTime The modified time of the config data.
 * @return AssetConfig
 */
	protected static function _parseConfig($baseFile, $constants, $modifiedTime = null) {
		if (!$modifiedTime && file_exists($baseFile)) {
			$modifiedTime = filemtime($baseFile);
		}

		$AssetConfig = new AssetConfig(self::$_defaults, $constants, $modifiedTime);
		self::_parseConfigFileLocal($baseFile, $AssetConfig);

		$plugins = CakePlugin::loaded();
		foreach ($plugins as $plugin) {
			$pluginConfig = CakePlugin::path($plugin) . 'Config' . DS . 'asset_compress.ini';
			if (file_exists($pluginConfig)) {
				self::_parseConfigFileLocal($pluginConfig, $AssetConfig, $plugin . '.');
			}
		}

		if ($AssetConfig->general('cacheConfig')) {
			Cache::write(self::CACHE_ASSET_CONFIG_KEY, $AssetConfig, self::CACHE_CONFIG);
		}

		return $AssetConfig;
	}

/**
 * Parse a file and optionally the .local version of the file.
 *
 * @param string $file The file to parse.
 * @param AssetConfig $AssetConfig The config object to add data to.
 * @param null|string $prefix The prefix to append to build targets.
 * @return void
 */
	protected static function _parseConfigFileLocal($file, $AssetConfig, $prefix = null) {
		self::_parseConfigFile($file, $AssetConfig, $prefix);

		// Load related .local.ini file if exists
		$localConfig = preg_replace('/(.*)\.ini$/', '$1.local.ini', $file);
		if (file_exists($localConfig)) {
			self::_parseConfigFile($localConfig, $AssetConfig, $prefix);
		}
	}

/**
 * Reads a config file and applies it to the given config instance
 *
 * @param string $iniFile Contents to apply to the config instance.
 * @param AssetConfig $AssetConfig The config instance instance we're applying this config file to.
 * @param string $prefix Prefix for the target key
 */
	protected static function _parseConfigFile($iniFile, $AssetConfig, $prefix = '') {
		$config = self::_readConfig($iniFile);

		foreach ($config as $section => $values) {
			if (in_array($section, self::$_extensionTypes)) {
				// extension section, merge in the defaults.
				$defaults = $AssetConfig->get($section);
				if ($defaults) {
					$values = array_merge($defaults, $values);
				}
				$AssetConfig->addExtension($section, $values);

			} elseif (strtolower($section) === self::GENERAL) {
				$AssetConfig->set(self::GENERAL, $values);

			} elseif (strpos($section, self::FILTER_PREFIX) === 0) {
				// filter section.
				$name = str_replace(self::FILTER_PREFIX, '', $section);
				$AssetConfig->filterConfig($name, $values);

			} else {
				$lastDot = strrpos($section, '.') + 1;
				$extension = substr($section, $lastDot);
				$key = $section;

				// Is there a prefix? Chop it off.
				if (strpos($section, $extension . '_') !== false) {
					$key = substr($key, strlen($extension) + 1);
				}

				// must be a build target.
				$AssetConfig->addTarget($prefix . $key, $values);
			}
		}
	}

/**
 * Add/Replace an extension configuration.
 *
 * @param string $ext Extension name
 * @param array $config Configuration for the extension
 * @return void
 */
	public function addExtension($ext, array $config) {
		$this->_data[$ext] = $this->_parseExtensionDef($config);
		if (!empty($this->_data[$ext][self::FILTERS])) {
			foreach ($this->_data[$ext][self::FILTERS] as $filter) {
				if (empty($this->_data[self::FILTERS][$filter])) {
					$this->_data[self::FILTERS][$filter] = array();
				}
			}
		}
	}

/**
 * Parses paths in an extension definition
 *
 * @param array $data Array of extension information.
 * @return array Array of build extension information with paths replaced.
 */
	protected function _parseExtensionDef($target) {
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
	protected function _replacePathConstants($path) {
		$result = strtr($path, $this->constantMap);
		return $result;
	}

/**
 * Set values into the config object, You can't modify targets, or filters
 * with this. Use the appropriate methods for those settings.
 *
 * @param string $path The path to set.
 * @param string $value The value to set.
 * @throws RuntimeException
 */
	public function set($path, $value) {
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
	public function get($path) {
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
 * Get/set filters for an extension/build file
 *
 * @param string $ext Name of an extension
 * @param string $target A build target. If provided the target's filters (if any) will also be
 *     returned.
 * @param array $filters Filters to replace either the global or per target filters.
 * @return array Filters for that extension.
 */
	public function filters($ext, $target = null, $filters = null) {
		if ($filters === null) {
			if (!isset($this->_data[$ext][self::FILTERS])) {
				$filters = array();
			} else {
				$filters = (array)$this->_data[$ext][self::FILTERS];
			}
			if ($target !== null && !empty($this->_data[$ext][self::TARGETS][$target][self::FILTERS])) {
				$buildFilters = $this->_data[$ext][self::TARGETS][$target][self::FILTERS];
				$filters = array_merge($filters, $buildFilters);
			}
			return array_unique($filters);
		}
		if ($target === null) {
			$this->_data[$ext][self::FILTERS] = $filters;
			foreach ($filters as $f) {
				if (empty($this->_data[self::FILTERS][$f])) {
					$this->_data[self::FILTERS][$f] = array();
				}
			}
		} else {
			$this->_data[$ext][self::TARGETS][$target][self::FILTERS] = $filters;
		}
	}

/**
 * Get/Set filter Settings.
 *
 * @param string $filter The filter name
 * @param array $settings The settings to set, leave null to get
 * @return mixed.
 */
	public function filterConfig($filter, $settings = null) {
		if ($settings === null) {
			if (is_string($filter)) {
				return isset($this->_data[self::FILTERS][$filter]) ? $this->_data[self::FILTERS][$filter] : array();
			}
			if (is_array($filter)) {
				$result = array();
				foreach ($filter as $f) {
					$result[$f] = $this->filterConfig($f);
				}
				return $result;
			}
		}
		$this->_data[self::FILTERS][$filter] = $settings;
	}

/**
 * Get/set the list of files that match the given build file.
 *
 * @param string $target The build file with extension.
 * @return array An array of files for the chosen build.
 */
	public function files($target, $files = null) {
		$ext = $this->getExt($target);
		if ($files === null) {
			if (isset($this->_data[$ext][self::TARGETS][$target]['files'])) {
				return (array)$this->_data[$ext][self::TARGETS][$target]['files'];
			}
			return array();
		}
		$this->_data[$ext][self::TARGETS][$target]['files'] = $files;
	}

/**
 * Get the extension for a filename.
 *
 * @param string $file
 * @return string
 */
	public function getExt($file) {
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
	public function paths($ext, $target = null, $paths = null) {
		if ($paths === null) {
			if (empty($this->_data[$ext]['paths'])) {
				$paths = array();
			} else {
				$paths = (array)$this->_data[$ext]['paths'];
			}
			if ($target !== null && !empty($this->_data[$ext][self::TARGETS][$target]['paths'])) {
				$buildPaths = $this->_data[$ext][self::TARGETS][$target]['paths'];
				$paths = array_merge($paths, $buildPaths);
			}
			return array_unique($paths);
		}

		$paths = array_map(array($this, '_replacePathConstants'), $paths);
		if ($target === null) {
			$this->_data[$ext]['paths'] = $paths;
		} else {
			$this->_data[$ext][self::TARGETS][$target]['paths'] = $paths;
		}
	}

/**
 * Accessor for getting the cachePath for a given extension.
 *
 * @param string $ext Extension to get paths for.
 * @param string $path The path to cache files using $ext to.
 */
	public function cachePath($ext, $path = null) {
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
	public function general($key, $value = null) {
		if ($value === null) {
			return isset($this->_data[self::GENERAL][$key]) ? $this->_data[self::GENERAL][$key] : null;
		}
		$this->_data[self::GENERAL][$key] = $value;
	}

/**
 * Get the build targets for an extension.
 *
 * @param string $ext The extension you want targets for.
 * @return array An array of build targets for the extension.
 */
	public function targets($ext) {
		if (empty($this->_data[$ext][self::TARGETS])) {
			return array();
		}
		return array_keys($this->_data[$ext][self::TARGETS]);
	}

/**
 * Create a new build target.
 *
 * @param string $target Name of the target file. The extension will be inferred based on the last extension.
 * @param array $config Config data for the target. Should contain files, filters and theme key.
 * @param array $filters The filters for the build (deprecated)
 */
	public function addTarget($target, array $config, $filters = array()) {
		$ext = $this->getExt($target);

		if (!empty($filters) || !isset($config['files'])) {
			// old method behavior.
			$config = array(
				'files' => $config,
				'filters' => $filters,
				'theme' => false
			);
		}
		if (!empty($config['paths'])) {
			$config['paths'] = array_map(array($this, '_replacePathConstants'), (array)$config['paths']);
		}
		$this->_data[$ext][self::TARGETS][$target] = $config;
	}

/**
 * Set the active theme for building assets.
 *
 * @param string $theme The theme name to set. Null to get
 * @return mixed Either null on set, or theme on get
 */
	public function theme($theme = null) {
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
	public function isThemed($target) {
		$ext = $this->getExt($target);
		return !empty($this->_data[$ext][self::TARGETS][$target]['theme']);
	}

/**
 * Get the list of extensions this config object supports.
 *
 * @return array Extension list.
 */
	public function extensions() {
		$exts = array_flip(array_keys($this->_data));
		unset($exts[self::FILTERS], $exts[self::GENERAL]);
		return array_keys($exts);
	}

/**
 * Check if a build target exists.
 *
 * @param string $file Name of the build file to check.
 * @return boolean Exists
 */
	public function exists($target) {
		$ext = $this->getExt($target);
		return !empty($this->_data[$ext][self::TARGETS][$target]);
	}

/**
 * Get the modified time of the config object.
 *
 * @return integer
 */
	public function modifiedTime() {
		return $this->_modifiedTime;
	}

}
