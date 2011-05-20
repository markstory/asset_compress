<?php
App::import('Core', 'Folder');
App::import('Model', 'AssetCompress.AssetScanner');

/**
 * Resource compressor base class for File compacting models.
 *
 */
abstract class AssetCompressor {
/**
 * config.ini key name for this asset types configuration.
 *
 * @var string
 **/
	protected $_configKeyName = '';

/**
 * Extension value.
 *
 * @var string
 */
	protected $_extension = null;

/**
 * An array of settings, these values are merged with the ones defined in the ini file.
 *
 * - `searchPaths` - Array of DS terminated Paths to load files from. Dirs will not be recursively scanned.
 * - `stripComments` - Remove inline comments? Deprecated, use filters for this.
 * - `cacheFilePath` - File path cached files should be saved to.
 * - `cacheFiles` - Should cache files be made.
 * - `filters` - Attached filters, contains only the string names of the filters.
 *
 * @var array
 */
	public $settings = array(
		'searchPaths' => array(),
		'stripComments' => false,
		'cacheFilePath' => null,
		'cacheFiles' => false,
		'filters' => array(),
		'timestamp' => false
	);

/**
 * Filename that contains the timestamp value for stamping files.
 *
 * @var string
 */
	protected $_timestampFilename = 'asset_compress_build_time';

/**
 * Processor objects that will be run
 *
 * @var array
 */
	protected $_processorObjects = array();

/**
 * Filter objects that will be run
 *
 * @var array
 */
	protected $_filterObjects = array();

/**
 * An array of already loaded + processed files, used to prevent double inclusion and infinite loops.
 *
 * @var array
 **/
	protected $_loaded = array();

/**
 * buffer for processed Output
 *
 * @var string
 **/
	protected $_processedOutput = '';

	protected $_Scanner = null;

/**
 * constructor for the model
 *
 * @return void
 **/
	public function __construct($iniFile = null) {
		if (empty($iniFile) || is_array($iniFile)) {
			$iniFile = CONFIGS . 'asset_compress.ini';
		}
		if (!file_exists($iniFile)) {
			$iniFile = App::pluginPath('AssetCompress') . 'config' . DS . 'config.ini';
		}
		$this->_readConfig($iniFile);
	}

/**
 * Reads the configuration file and copies out settings into member vars
 *
 * @param string $filename Name of config file to load.
 * @return void
 **/
	protected function _readConfig($filename) {
		if (empty($filename) || !is_string($filename) || !file_exists($filename)) {
			return false;
		}
		$iniSettings = parse_ini_file($filename, true);
		$this->settings = array_merge($this->settings, $iniSettings[$this->_configKeyName]);
		if (empty($this->settings['searchPaths'])) {
			throw new Exception('searchPaths was empty! Make sure you configured at least one searchPaths[] in your config.ini file');
		}
	}

/**
 * Process a set of Files / NamedObjects togehter resolving and directives as needed.
 * The files/NamedObjects must be on the searchPaths for things to work.
 *
 * If the configuration indicates the creation of cache files those files will be created
 *
 * @param array Array of files/NamedObjects to join together.
 * @return string String of Joined together files.
 **/
	public function process($objects) {
		$this->_Scanner = new AssetScanner($this->settings['searchPaths']);
		if (!is_array($objects)) {
			$objects = (array)$objects;
		}
		$out = '';
		foreach ($objects as $object) {
			$fileName = $this->_findFile($object);
			$this->_processedOutput .= $this->_applyProcessors($fileName, $this->_preprocess($fileName) . "\n");
		}
		$this->_applyFilters();
		$out = trim($this->_processedOutput);
		$this->reset();
		return $out;
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
 * resets the pre-processor
 *
 * @return void
 **/
	public function reset() {
		$this->_loaded = array();
		$this->_processedOutput = '';
	}

/**
 * Applies the pre-processors to the contents of a file.
 *
 * @param string $fileName The name for a file.
 * @param string $contents The contents of a file.
 * @return string The processed contents of the input.
 */
	protected function _applyProcessors($fileName, $contents) {
		if (!empty($this->settings['processors'])) {
			$this->_loadExtensions('processors');
			if (!empty($this->_processorObjects)) {
				foreach ($this->_processorObjects as $processor) {
					$contents = $processor->process($fileName, $contents);
				}
			}
		}
		return $contents;
	}

/**
 * Apply the defined filter sets to the processedOutput.
 *
 * Filters are defined in the config file as a class to run.  This class must implement the
 * `AssetFilterInterface` in the plugin.  Each filter will be run in the order defined in
 * the configuration file.
 *
 * The method should return the results of its application as a string.
 * If you use more than one filter, make sure they don't clobber each other :)
 *
 * @return void
 * @throws Exception
 */
	protected function _applyFilters() {
		if (empty($this->settings['filters'])) {
			return;
		}
		$this->_loadExtensions();
		if (empty($this->_filterObjects)) {
			return;
		}
		$output = $this->_processedOutput;
		foreach ($this->_filterObjects as $filter) {
			$output = $filter->filter($output);
		}
		$this->_processedOutput = $output;
	}

/**
 * Loads extensions defined in $filters or $processors from the app/libs dir.
 *
 * @param string $type The type of extension to load.
 * @return void
 */
	protected function _loadExtensions($type = 'filters') {
		$singular = Inflector::singularize($type);
		$suffix = Inflector::camelize($singular);
		foreach ($this->settings[$type] as $extension) {
			$className = $extension . $suffix;
			list($plugin, $className) = pluginSplit($className, true);
			App::import('Lib', $plugin . 'asset_compress/' . $extension);
			if (!class_exists($className)) {
				App::import('Lib', 'AssetCompress.' . $singular . '/' . $extension);
				if (!class_exists($className)) {
					throw new Exception(sprintf('Cannot not load %s.', $className));
				}
			}
			$extensionObj = new $className();
			$interface = 'Asset' . $suffix . 'Interface';
			if (!is_a($extensionObj, $interface)) {
				throw new Exception('Cannot use ' . $type . ' that do not implement ' . $interface);
			}
			$property = '_' . $singular . 'Objects';
			array_push($this->$property, $extensionObj);
		}
	}

/**
 * Check if caching is on for this asset.
 *
 * @return void
 */
	public function cachingOn() {
		return $this->settings['cacheFiles'] && !empty($this->settings['cacheFilePath']);
	}

/**
 * Find the file that matches an $object name.
 *
 * @return string The path to $object's file.
 **/
	protected function _findFile($object, $path = null) {
		if ($this->getFileExtension($object) != $this->_extension) {
			$object = "{$object}.{$this->_extension}";
		}
		$filename = $this->_Scanner->find($object);
		if (!$filename) {
			throw new Exception('Could not locate file for ' . $object);
		}
		return $filename;
	}

/**
 * Preprocess the file as needed
 *
 * @param string $filename name of file to process
 * @return string The processed file contents
 **/
	protected function _preprocess($filename) {
		if (isset($this->_loaded[$filename])) {
			return '';
		}
		$this->_loaded[$filename] = true;
		return file_get_contents($filename);
	}

/**
 * get the header line for a cached file.
 * Helps denote files cached by plugin so they can be cleared.
 *
 * @return string File header string.
 */
	protected function _getFileHeader() {
		return "/* asset_compress " . time() . " */\n";
	}

/**
 * get the directory cached files are written to.
 *
 * @return string Path for files.
 */
	public function cacheDir() {
		return $this->_replacePathConstants($this->settings['cacheFilePath']);
	}

/**
 * Write a cache file for a specific key/
 *
 * @param string $key The filename to write.
 * @param string $content The content to write.
 * @return boolean sucess of write operation.
 */
	public function cache($key, $content) {
		$writeDirectory = $this->_replacePathConstants($this->settings['cacheFilePath']);
		if (!is_writable($writeDirectory)) {
			throw new Exception(sprintf('Cannot write to %s bailing on writing cache file.', $writeDirectory));
		}
		$header = $this->_getFileHeader();
		if ($this->settings['timestamp']) {
			$key = $this->_timestampFilename($key);
		}
		$filename = $writeDirectory . $key;
		return file_put_contents($filename, $header . $content);
	}

/**
 * Creates a file in APP/tmp that contains a timestamp this file
 * is used for timestampping assets as they get built.
 *
 * @return void
 */
	public function createBuildTimestamp() {
		$path = TMP . $this->_timestampFilename;
		$time = time();
		file_put_contents($path, $time);
	}

/**
 * Clears the build timestamp file.
 *
 * @return void
 */
	public function clearBuildTimestamp() {
		unlink(TMP . $this->_timestampFilename);
	}

/**
 * Adds additional searchPaths for the Theme parameter.
 * duplicates all the existing configured paths and adds in a theme version.
 *
 * @param string $theme The theme you are adding.
 * @return void
 */
	public function addTheme($theme) {
		$themePath = 'APP/views/themed/' . $theme . '/webroot';

		$viewPaths = App::path('views');
		foreach ($viewPaths as $viewPath) {
			$path = $viewPath . 'themed' . DS . $theme . DS . 'webroot';
			if (is_dir($path)) {
				$themePath = $path;
				break;
			}
		}
		foreach (array_reverse($this->settings['searchPaths']) as $searchPath) {
			array_unshift(
				$this->settings['searchPaths'],
				str_replace('WEBROOT', $themePath, $searchPath)
			);
		}
	}

/**
 * Appends a timestamp after the last extension in a file name or simply appends
 * the timestamp if there is no extension.  Does not use the current time. This
 * would allow for DOS attacks by randomly hitting timestamp filenames and forcing the
 * server to build additional files. Instead a secondary
 * file is used for getting the timestamp.  To clear this file use the console.
 *
 * @return string filename with a timestamp added.
 * @see AssetCompressor::$timestampFilename
 */
	protected function _timestampFilename($name) {
		$ext = null;
		$dot = strrpos($name, '.');
		if ($dot !== false) {
			$ext = substr($name, $dot);
			$name = substr($name, 0, $dot);
		}
		$timestampFile = TMP . $this->_timestampFilename;
		if (file_exists($timestampFile)) {
			$timestamp = file_get_contents($timestampFile);
		} else {
			$timestamp = time();
			$this->createBuildTimestamp();
		}
		if (strpos($name, $timestamp) === false) {
			$name .= '.' . $timestamp;
		}
		return $name . $ext;
	}


/**
 * Inspect and returns the extension of passed filename
 *
 * @return string extension or null
 */
	public function getFileExtension($file) {
		if (empty($file)) {
			return null;
		}
		if ( ($pos = strrpos($file, '.')) !== false) {
			return substr($file, $pos + 1);
		}
		return null;
	}

/**
 * Check a file names extension against the Asset types
 *
 * @param string $file filename
 * @return boolean true if extension is valid for the calling model
 */
	public function validExtension($file) {
		if (!$this->_extension) {
			throw new Exception('Cannot check extension, as $_extension is empty.');
		}
		return $this->getFileExtension($file) == $this->_extension;
	}

}
