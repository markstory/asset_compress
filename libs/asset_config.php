<?php
/**
 * Parses the ini files AssetCompress uses into arrays that
 * other objects can use.
 *
 * @package asset_compress
 */
class AssetConfig {

	protected $_config = array();

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
	const TARGETS = 'targets';

/**
 * Constructor
 *
 * @param string $iniFile File path for the ini file to parse.
 */
	public function __construct($iniFile = null) {
		if (empty($iniFile) || is_array($iniFile)) {
			$iniFile = CONFIGS . 'asset_compress.ini';
		}
		if (!file_exists($iniFile)) {
			$iniFile = App::pluginPath('AssetCompress') . 'config' . DS . 'config.ini';
		}
		$this->_readConfig($iniFile);
		$this->_parseConfig();
	}

/**
 *
 * @param string $filename Name of the inifile to parse
 */
	protected function _readConfig($filename) {
		if (empty($filename) || !is_string($filename) || !file_exists($filename)) {
			throw new RuntimeException('No configuration file found.');
		}
		$this->_config = parse_ini_file($filename, true);
	}

/**
 * Transforms the config data into a more structured form
 *
 * @return void
 */
	protected function _parseConfig() {
		foreach ($this->_config as $section => $values) {
			if (strpos($section, '_') === false) {
				// extension section
				$this->_data[$section] = $this->_parseExtensionDef($values);
			} else {
				list($extension, $key) = explode('_', $section, 2);
				// global filters for the extension
				if ($key === self::FILTERS) {
					if (empty($values[self::FILTERS])) {
						throw new Exception(sprintf('No filters key in the "%s_filters" section.', $extension));
					}
					$this->_data[$extension][self::FILTERS] = $values[self::FILTERS];
				} else {
					// must be a build target.
					$this->_data[$extension][self::TARGETS][$key] = $values;
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
			if (isset($this->_data[$ext][self::FILTERS])) {
				$filters = (array)$this->_data[$ext][self::FILTERS];
				if ($target !== null && !empty($this->_data[$ext][self::TARGETS][$target][self::FILTERS])) {
					$filters = array_merge($filters, (array)$this->_data[$ext][self::TARGETS][$target][self::FILTERS]);
				}
				return array_unique($filters);
			}
			return array();
		}
		if ($target === null) {
			$this->_data[$ext][self::FILTERS] = $filters;
		} else {
			$this->_data[$ext][self::TARGETS][$target][self::FILTERS] = $filters;
		}
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
