<?php
/**
 * Used for dynamic build files where a set of searchPaths
 * are declared in the config file.  This class allows you search through
 * those searchPaths and locate assets.
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
		$this->_expandPaths();
		$this->_normalizePaths();
	}

/**
 * Ensure all paths end in a DS and expand any APP/WEBROOT constants
 *
 * @return void
 */
	protected function _normalizePaths() {
		foreach ($this->_paths as &$path) {
			$path = rtrim($path, DS) . DS;
		}
	}

/**
 * Expands constants and glob() patterns in the searchPaths.
 *
 * @return void
 */
	protected function _expandPaths() {
		$expanded = array();
		foreach ($this->_paths as $path) {
			/*
			 * might need to change this regex but this is not issue presently
			 * looking at tests with JHREGEX Vs Original
			 *  2 tests fail in each but testFind & testExpandGlob fail with original
			 *  whereas with JHREGEX testExpandGlob fails twice (testFind passes) - windows not yet looked at *nix
			 */
			#JHREGEX /^(\d{2}:)?[-\w.+]*\/[-\w.+]+:[\*\.a-zA-Z0-9]*$/
			#ORIGINAL /[*.\[\]]/
			if (preg_match('/^(\d{2}:)?[-\w.+]*\/[-\w.+]+:[\*\.a-zA-Z0-9]*$/', $path)) {
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
		$paths = glob($path, GLOB_ONLYDIR);
		return $paths;
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
