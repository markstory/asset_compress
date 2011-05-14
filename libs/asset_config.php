<?php

/**
 * Parses the ini files AssetCompress uses into arrays that
 * other objects can use.
 *
 * @pacakge asset_compress
 */
class AssetConfig {

	protected $_config = array();

	protected $_data = array();

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

			// extension section
			if (strpos($section, '_') === false) {
				$this->_data[$section] = $values;
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
 * Simple read accessor to parsed data.
 *
 * @param string $name
 */
	public function __get($name) {
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		return null;
	}

/**
 * Get filters for an extension/build file
 *
 * @param string $ext Name of an extension
 * @param string $target A build target. If provided the target's filters (if any) will also be 
 *     returned.
 * @return array Filters for that extension.
 **/
	public function filters($ext, $target = null) {
		if (isset($this->_data[$ext][self::FILTERS])) {
			$filters = (array)$this->_data[$ext][self::FILTERS];
			if ($target !== null && !empty($this->_data[$ext][self::TARGETS][$target][self::FILTERS])) {
				$filters = array_merge($filters, (array)$this->_data[$ext][self::TARGETS][$target][self::FILTERS]);
			}
			return array_unique($filters);
		}
		return array();
	}

}
