<?php
/**
 * Scan a set of paths for files with the correct criteria.
 *
 * @package asset_compress
 */
class AssetScanner {

/**
 * Paths this scanner should scan.
 *
 * @var array
 */
	protected $_paths = array();

	public function __construct(array $paths) {
		$this->_paths = $paths;
		$this->_normalizePaths();
		$this->_expandPaths();
	}

/**
 * Ensure all paths end in a DS.
 *
 * @return void
 */
	protected function _normalizePaths() {
		foreach ($this->_paths as &$path) {
			$path = rtrim($path, DS) . DS;
			$path = $this->_replacePathConstants($path);
		}
	}

/**
 * Replaces the file path constants used in Config files.
 * Will replace APP and WEBROOT
 *
 * @param string $path Path to replace constants on
 * @return string constants replaced
 */
	protected function _replacePathConstants($path) {
		$constantMap = array('APP/' => APP, 'WEBROOT/' => WWW_ROOT);
		return str_replace(array_keys($constantMap), array_values($constantMap), $path);
	}

/**
 * Takes any configured path that ends in * and expands that to be all 
 * directories within it.
 *
 * @return void
 */
	protected function _expandPaths() {
		$expanded = array();
		foreach ($this->_paths as $path) {
			if (substr($path, -1) == '*') {
				$tree = $this->_generateTree($path);
				$expanded = array_merge($expanded, $tree);
			} else {
				$expanded[] = $path;
			}
		}
		$this->_paths = $expanded;
	}

/**
 * Discover all the sub directories for a given path.
 *
 * @param string $path The path to search
 * @return array Array of subdirectories.
 */
	protected function _generateTree($path) {
		return array();
	}

/**
 * Find a file in the connected paths, and read its contents. 
 *
 * @param string $file The file you want to find.
 * @return mixed Either false on a miss, or the contents of the file.
 */
	public function find($file) {
		foreach ($this->_paths as $path) {
			if (file_exists($path . $file)) {
				return $path . $file;
			}
		}
		return false;
	}

/**
 * Accessor for paths.
 *
 * @return array an array of paths.
 */
	public function paths() {
		return $this->_paths;
	}

}
