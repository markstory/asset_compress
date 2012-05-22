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
			if (!($ds = $this->isRemote($path))) {
				// Remote paths are normalized to their own DS, else they will be normalized to Cake's DS const.
				$ds = DS;
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
		return str_replace(array('/','\\'), $ds, $name);
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
		array_unshift($paths, dirname($path));
		return $paths;
	}

/**
 * Find a file in the connected paths, and check for its existance.
 *
 * @param string $file The file you want to find.
 * @return mixed Either false on a miss, or the full path of the file.
 */
	public function find($file) {
		$changed = false;
		if ($this->_theme && preg_match(self::THEME_PATTERN, $file)) {
			$changed = true;
			$file = $this->_resolveTheme($file);
		}
		if (preg_match(self::PLUGIN_PATTERN, $file)) {
			$changed = true;
			$file = $this->_resolvePlugin($file);
		}
		if ($changed && file_exists($file)) {
			return $file;
		}
		foreach ($this->_paths as $path) {
			if ($ds = $this->isRemote($path)) {
				$file = $this->_normalizePath($file, $ds);
				$full_path = $path . $file;
				// Opens and closes the remote file, just to check for its existance. Its contents will be read elsewhere.
				$handle = @fopen($full_path, 'rb');
				if ($handle) {
					fclose($handle);
					return $full_path;
				}
			} else {
				$file = $this->_normalizePath($file, DS);
				$full_path = $path . $file;
				if (file_exists($full_path)) {
					return $full_path;
				}
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
 * Resolve a plugin file to its full path.
 *
 * @param string $file The theme file to find.
 * @return string Full path to theme file.
 * @throws RuntimeException when plugins are missing.
 */
	protected function _resolvePlugin($file) {
		preg_match(self::PLUGIN_PATTERN, $file, $matches);
		if (empty($matches[1]) || empty($matches[2])) {
			throw new RuntimeException('Missing required parameters');
		}
		if (!CakePlugin::loaded($matches[1])) {
			throw new RuntimeException($matches[1] . ' is not a loaded plugin.');
		}
		$path = CakePlugin::path($matches[1]);
		return $path . 'webroot' . DS . $matches[2];
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
 * @param string $target
 * @return mixed If $target is a handable remote resource, it will return its Directory Separator character. False if not.
 *		This doesn't seem to be very logic, but simplifies things. It's compatible with a TRUE|FALSE logic though.
 */
	public function isRemote($target) {
		/* 
		 * Patterns for matching readable remote resources
		 * Make sure that any included pattern will be accepted by fopen() as well.
		 * Please surround the directory separator with (). It will be used for path normalization.
		 */
		$remotePatterns = array(
			'/^http:\/(\/)/i' // Just HTTP protocol for now, but anything that fopen() can read should work.
		);
		
		$matches = array();
		foreach ($remotePatterns as $pattern) {
			if (preg_match($pattern, $target, $matches)) {
				return $matches[1]; // The first match is expected to be the Directory Separator. Please see the definition of $remotePatterns above.
			}
		}
			
		return false;
	}
}
