<?php
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
 * Properties to be read when parsing the ini file
 *
 * @var array
 **/
	protected $_configProperties = array();

/**
 * Paths to search files on.
 *
 * @var array Array of DS terminated Paths to load files from. Dirs will not be recursively scanned.
 **/
	public $searchPaths = array();

/**
 * Remove inline comments?
 *
 * @var boolean
 **/
	public $stripComments = false;

/**
 * File path cached files should be saved to.
 *
 * @var string
 */
	public $cacheFilePath = null;

/**
 * Should cache files be made.
 *
 * @var boolean
 */
	public $cacheFiles = false;

/**
 * Flag for keeping track comment block status.
 *
 * @var boolean
 **/
	protected $_inCommentBlock = false;

/**
 * Contains a hashmap of path -> filescans
 *
 * @var array
 **/
	protected $_fileLists;

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

/**
 * constructor for the model
 *
 * @return void
 **/
	public function __construct($iniFile = null) {
		$this->_Folder = new Folder(APP);
		if (!is_string($iniFile) || empty($iniFile)) {
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
		$settings = parse_ini_file($filename, true);
		foreach ($this->_configProperties as $name) {
			if (isset($settings[$this->_configKeyName][$name])) {
				$this->{$name} = $settings[$this->_configKeyName][$name];
			}
		}
		if (empty($this->searchPaths)) {
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
		if (!is_array($objects)) {
			$objects = (array)$objects;
		}
		$out = '';
		foreach ($objects as $object) {
			$fileName = $this->_findFile($object);
			$this->_preprocess($fileName);
		}
		/*
		TODO implement a filter set.  So you could add a number of classes/functions to apply to the 
		concatenated output.  Like compressors etc.
		*/
		$out = trim($this->_processedOutput);
		$this->reset();
		return $out;
	}

/**
 * Read all the $searchPaths and cache the files inside of each.
 *
 * @return void
 **/
	protected function _readDirs() {
		foreach ($this->searchPaths as $i => $path) {
			$this->searchPaths[$i] = $this->_replacePathConstants($path);
		}
		foreach ($this->searchPaths as $path) {
			$this->_Folder->cd($path);
			list($dirs, $files) = $this->_Folder->read();
			$this->_fileLists[$path] = $files;
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
		$constantMap = array('APP' => APP, 'WEBROOT' => WWW_ROOT);
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
 * Records a line to the buffer.  Strips comments if that has been enabled.
 *
 * @return void
 **/
	protected function _record($line) {
		if ($this->stripComments) {
			$this->_processedOutput .= $this->_stripComments($line);
			return;
		}
		$this->_processedOutput .= $line;
	}

/**
 * Check if caching is on for this asset.
 *
 * @return void
 */
	public function cachingOn() {
		return $this->cacheFiles && !empty($this->cacheFilePath);
	}

/**
 * Find the file that matches an $object name.
 *
 * @return string The path to $object's file.
 **/
	abstract protected function _findFile($object);

/**
 * Preprocess the file as needed
 *
 * @param string $filename name of file to process
 * @return string The processed file contents
 **/
	abstract protected function _preprocess($filename);

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
 * Write a cache file for a specific key/
 *
 * @param string $key The filename to write.
 * @param string $content The content to write.
 * @return void
 */
	public function cache($key, $content) {
		$writeDirectory = $this->_replacePathConstants($this->cacheFilePath);
		if (!is_writable($writeDirectory)) {
			throw new Exception(sprintf('Cannot write to %s bailing on writing cache file.', $writeDirectory));
		}
		$header = $this->_getFileHeader();
		$filename = $writeDirectory . $key;
		$file = new File($filename, true);
		return $file->write($header . $content);
	}

}
