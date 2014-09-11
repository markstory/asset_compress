<?php
/**
 * Used for dynamic build files where a set of searchPaths
 * are declared in the config file. This class allows you search through
 * those searchPaths and locate assets.
 *
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
 * @const Pattern for various prefixes.
 */
	const THEME_PATTERN = '/^(?:t|theme)\:/';
	const PLUGIN_PATTERN = '/^(?:p|plugin)\:(.*)\:(.*)$/';

	public function __construct(array $paths, $theme = null) {
		$this->_theme = $theme;
		$this->_paths = $paths;
		$this->_expandPaths();
		$this->_normalizePaths();
	}

/**
 * Ensure all paths end in a DS and expand any APP/WEBROOT constants.
 * Normalizes the Directory Separator as well.
 * @return void
 */
	protected function _normalizePaths() {
		foreach ($this->_paths as &$path) {
			$ds = DS;
			if ($this->isRemote($path)) {
				$ds = '/';
			}
			$path = $this->_normalizePath($path, $ds);
			$path = rtrim($path, $ds) . $ds;
		}
	}

/**
 * Normalize a file path to the specified Directory Separator ($ds)
 * @param string $name Path to normalize
 * @param type $ds Directory Separator to be used
 * @return string Normalized path
 */
	protected function _normalizePath($name, $ds) {
		return str_replace(array('/', '\\'), $ds, $name);
	}

/**
 * Expands constants and glob() patterns in the searchPaths.
 *
 * @return void
 */
	protected function _expandPaths() {
		$expanded = array();
		foreach ($this->_paths as $path) {
			if ($this->isRemote($path)) {
				// Remote path. Not expandable!
				$expanded[] = $path;
			} elseif (preg_match('/[*.\[\]]/', $path)) {
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
		if (!$paths) {
			$paths = array();
		}
		array_unshift($paths, dirname($path));
		return $paths;
	}

/**
 * Find a file in the connected paths, and check for its existance.
 *
 * @param string $file The file you want to find.
 * @param boolean $absolute Set to false to get relative URL compatible paths.
 * @return mixed Either false on a miss, or the full path of the file.
 */
	public function find($file, $absolute = true) {
		$expanded = $this->_expandPrefix($file);

		if (isset($expanded['absolute']) && file_exists($expanded['absolute'])) {
			$key = $absolute ? 'absolute' : 'relative';
			return $expanded[$key];
		}

		foreach ($this->_paths as $path) {
			if ($this->isRemote($path)) {
				$file = $this->_normalizePath($file, '/');
				$fullPath = $path . $file;
				// Opens and closes the remote file, just to
				// check for its existance. Its contents will be read elsewhere.
				// @codingStandardsIgnoreStart
				$handle = @fopen($fullPath, 'rb');
				// @codingStandardsIgnoreStart
				if ($handle) {
					fclose($handle);
					return $fullPath;
				}
			} else {
				$file = $this->_normalizePath($file, DS);
				$fullPath = $path . $file;

				$exists = file_exists($fullPath);

				if ($absolute === false && $exists) {
					$expanded['relative'] = str_replace(WWW_ROOT, '/', $expanded['relative']);
				}
				if ($exists) {
					$expanded['absolute'] = $fullPath;
					break;
				}
			}
		}

		// Could not find absolute file path.
		if (empty($expanded['absolute'])) {
			return false;
		}
		$key = $absolute ? 'absolute' : 'relative';
		return $expanded[$key];
	}

/**
 * Resolve a plugin or theme path into the file path without the search paths.
 *
 * @param string $path Path to resolve
 * @param boolean $full Gives absolute paths
 * @return string resolved path
 */
	protected function _expandPrefix($path) {
		if (preg_match(self::PLUGIN_PATTERN, $path)) {
			return $this->_expandPlugin($path);
		}
		if ($this->_theme && preg_match(self::THEME_PATTERN, $path)) {
			return $this->_expandTheme($path);
		}
		return array('relative' => $path);
	}

/**
 * Resolve a themed file to its full path. The file will be found on the
 * current theme's path.
 *
 * @param string $file The theme file to find.
 * @return array An array of the relative and absolute paths.
 */
	protected function _expandTheme($file) {
		$file = preg_replace(self::THEME_PATTERN, '', $file);
		return array(
			'absolute' => App::themePath($this->_theme) . 'webroot' . DS . $file,
			'relative' => DS . 'theme' . DS . Inflector::underscore($this->_theme) . DS . $file,
		);
	}

/**
 * Resolve a plugin file to its full path.
 *
 * @param string $file The theme file to find.
 * @throws RuntimeException when plugins are missing.
 * @return array An array of the relative and absolute paths.
 */
	protected function _expandPlugin($file) {
		preg_match(self::PLUGIN_PATTERN, $file, $matches);
		if (empty($matches[1]) || empty($matches[2])) {
			throw new RuntimeException('Missing required parameters');
		}
		if (!CakePlugin::loaded($matches[1])) {
			throw new RuntimeException($matches[1] . ' is not a loaded plugin.');
		}
		$path = CakePlugin::path($matches[1]);
		return array(
			'absolute' => $path . 'webroot' . DS . $matches[2],
			'relative' => DS . Inflector::underscore($matches[1]) . DS . $matches[2],
		);
	}

/**
 * Accessor for paths.
 *
 * @return array an array of paths.
 */
	public function paths() {
		return $this->_paths;
	}

/**
 * Checks if a string represents a remote file
 *
 * @param string $target
 * @return boolean If $target is a handable remote resource.:
 */
	public function isRemote($target) {
		// Patterns for matching readable remote resources
		// Make sure that any included pattern will
		// be accepted by fopen() as well.
		$remotePatterns = array(
			'/^https?:\/\//i'
		);
		$matches = array();
		foreach ($remotePatterns as $pattern) {
			if (preg_match($pattern, $target, $matches)) {
				return true;
			}
		}
		return false;
	}

}
