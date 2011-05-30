<?php
/**
 * Parses the ini files AssetCompress uses into arrays that
 * other objects can use.
 *
 * @package asset_compress
 */
class AssetConfig {

	protected $_data = array();

/**
 * A hash of constants that can be expanded when reading ini files.
 *
 * @var array
 */
	public $constantMap = array(
		'APP/' => APP, 
		'WEBROOT/' => WWW_ROOT
	);

	const FILTERS = 'filters';
	const FILTER_PREFIX = 'filter_';
	const TARGETS = 'targets';

/**
 * Constructor, set some initial data for a AssetConfig object. 
 *
 * @param array $data
 */
	public function __construct(array $data = array()) {
		$this->_data = $data;
	}

/**
 * Constructor
 *
 * @param string $iniFile File path for the ini file to parse.
 */
	public static function buildFromIniFile($iniFile = null) {
		if (empty($iniFile) || is_array($iniFile)) {
			$iniFile = CONFIGS . 'asset_compress.ini';
		}
		if (!file_exists($iniFile)) {
			$iniFile = App::pluginPath('AssetCompress') . 'config' . DS . 'config.ini';
		}
		$contents = self::_readConfig($iniFile);
		return self::_parseConfig($contents);
	}

/**
 *
 * @param string $filename Name of the inifile to parse
 */
	protected static function _readConfig($filename) {
		if (empty($filename) || !is_string($filename) || !file_exists($filename)) {
			throw new RuntimeException('No configuration file found.');
		}
		return parse_ini_file($filename, true);
	}

/**
 * Transforms the config data into a more structured form
 *
 * @param array $contents Contents to build a config object from.
 * @return AssetConfig
 */
	protected static function _parseConfig($config) {
		$AssetConfig = new AssetConfig();
		foreach ($config as $section => $values) {
			if (strpos($section, '_') === false) {
				// extension section
				$AssetConfig->addExtension($section, $values);
			} elseif (strpos($section, self::FILTER_PREFIX) === 0) {
				// filter section.
				$name = str_replace(self::FILTER_PREFIX, '', $section);
				$AssetConfig->filterConfig($name, $values);
			} else {
				list($extension, $key) = explode('_', $section, 2);
				// must be a build target.
				$files = isset($values['files']) ? $values['files'] : array();
				$filters = isset($values['filters']) ? $values['filters'] : array();
				$AssetConfig->addTarget($key, $files, $filters);
			}
		}
		return $AssetConfig;
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
 * Parses paths in an extension definintion
 *
 * @param array $data Array of extension information.
 * @return array Array of build extension information with paths replaced.
 */
	protected function _parseExtensionDef($target) {
		$paths = array();
		if (!empty($target['paths'])) {
			$paths = array_map(array($this, '_replacePathConstants'), (array) $target['paths']);
		}
		$target['paths'] = $paths;
		if (!empty($target['cachePath'])) {
			$target['cachePath'] = $this->_replacePathConstants($target['cachePath']);
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
		return str_replace(array_keys($this->constantMap), array_values($this->constantMap), $path);
	}

/**
 * Simple read accessor to parsed data.
 *
 * @param string $name
 */
	public function __get($name) {
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		if (isset($this->_data['General'][$name])) {
			return $this->_data['General'][$name];
		}
		return null;
	}

/**
 * Set values into the config object
 *
 * @param string $path The path to set.
 * @param string $value The value to set.
 */
	public function set($path, $value) {
		$this->_data = Set::insert($this->_data, $path, $value);
	}

/**
 * Get values from the config data.
 *
 * @param string $path The path you want.
 */
	public function get($path) {
		return Set::classicExtract($this->_data, $path);
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
				return array();
			}
			$filters = (array)$this->_data[$ext][self::FILTERS];
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
 * all existing paths. Its only intended for testing.
 *
 * @param string $ext Extension to get paths for.
 * @return array An array of paths to search for assets on.
 */
	public function paths($ext, $paths = null) {
		if ($paths === null) {
			if (!empty($this->_data[$ext]['paths'])) {
				return (array) $this->_data[$ext]['paths'];
			}
			return array();
		}
		$this->_data[$ext]['paths'] = array_map(array($this, '_replacePathConstants'), $paths);
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
		$this->_data[$ext]['cachePath'] = $this->_replacePathConstants($path);
	}

/**
 * Check to see if caching is on for an extension.
 * Caching is controlled by General.writeCache and the matching 
 * extension having a cachePath.
 *
 * @param string $target
 * @return boolean
 */
	public function cachingOn($target) {
		$ext = $this->getExt($target);
		if ($this->writeCache && $this->cachePath($ext)) {
			return true;
		}
		return false;
	}

/**
 * Create a new build target.
 *
 * @param string $target Name of the target file.  The extension will be inferred based on the last extension.
 * @param array $files Files to combine the build file from.
 */
	public function addTarget($target, array $files, $filters = array()) {
		$ext = $this->getExt($target);
		$this->_data[$ext][self::TARGETS][$target] = array(
			'files' => $files,
			'filters' => $filters
		);
	}
}
