<?php
/**
 * Resource compressor base class for File compacting models.
 *
 */
abstract class AssetCompressor extends Object {
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
	public function __construct($iniFile = '') {
		$this->_Folder = new Folder(APP);
		if (!is_string($iniFile)) {
			$iniFile = $this->_pluginPath() . 'config' . DS . 'config.ini';
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
 * find the asset_compress path
 *
 * @return void
 **/
	protected function _pluginPath() {
		$paths = Configure::read('pluginPaths');
		foreach ($paths as $path) {
			if (is_dir($path . 'asset_compress')) {
				return $path . 'asset_compress' . DS;
			}
		}
		throw new Exception('Could not find my directory, bailing hard!');
	}
/**
 * Process a set of Files / NamedObjects togehter resolving and directives as needed.
 * The files/NamedObjects must be on the searchPaths for things to work.
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
		$constantMap = array('APP' => APP, 'WEBROOT' => WWW_ROOT);
		foreach ($this->searchPaths as $i => $path) {
			$this->searchPaths[$i] = str_replace(array_keys($constantMap), array_values($constantMap), $path);
		}
		foreach ($this->searchPaths as $path) {
			$this->_Folder->cd($path);
			list($dirs, $files) = $this->_Folder->read();
			$this->_fileLists[$path] = $files;
		}
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
}
