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

/**
 * The active theme the scanner could find assets on.
 *
 * @var string
 */
	protected $_theme = null;

/**
 * @const Pattern for theme prefixes.
 */
	const THEME_PATTERN = '/^(?:t|theme)\:/';

	public function __construct(array $paths, $theme = null) {
		$this->_theme = $theme;
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
			if (preg_match('/[*.\[\]]/', $path)) {
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
		array_unshift($paths, dirname($path));
		return $paths;
	}

/**
 * Find a file in the connected paths, and read its contents.
 *
 * @param string $file The file you want to find.
 * @return mixed Either false on a miss, or the contents of the file.
 */
	public function find($file) {
		$changed = false;
		if ($this->_theme && preg_match(self::THEME_PATTERN, $file)) {
			$changed = true;
			$file = $this->_resolveTheme($file);
		}
		if ($changed && file_exists($file)) {
			return $file;
		}
		foreach ($this->_paths as $path) {
			if (file_exists($path . $file)) {
				return $path . $file;
			}
		}
		return false;
	}

/**
 * Resolve a themed file to its full path. The file will be found on the
 * current theme's path.
 *
 * @param string $file The theme file to find.
 * @return string Full path to theme file.
 */
	protected function _resolveTheme($file) {
		$file = preg_replace(self::THEME_PATTERN, '', $file);
		return App::themePath($this->_theme) . 'webroot' . DS . $file;
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
