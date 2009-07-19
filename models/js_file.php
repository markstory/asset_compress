<?php
/**
 * Javascript File Preprocessor model.
 * Preprocesss JS files for //= require statements.
 * 
 * Uses Plugin config.ini file for path and other directive information.
 *
 * @package asset_compress
 * @author Mark Story
 **/
class JsFile extends AssetCompressAppModel {
	public $name = 'JsFile';

	public $useTable = false;

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
 * pattern for finding dependancies.
 *
 * @var string
 **/
	public $requirePattern = '/^\s?\/\/\s*\=\s*require\s([\"|<])([^\"\>]+)\1/';
/**
 * constructor for the model
 *
 * @return void
 **/
	public function __construct($id = null, $table = null, $ds = null) {
		$this->_Folder = new Folder(APP);
		$this->_readConfig();
	}
/**
 * Reads the configuration file and copies out settings into member vars
 *
 * @return void
 **/
	protected function _readConfig() {
		
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
		$out = $this->_processedOutput;
		$this->reset();
		return $out;
	}
/**
 * Scan each of the $searchPaths for the named object / filename
 *
 * @return string Full path to the $object
 **/
	protected function _findFile($object, $path = null) {
		$filename = Inflector::underscore($object) . '.js';
		if (empty($this->_fileLists)) {
			$this->_readDirs();
		}
		if ($path !== null) {
			return $path . $filename;
		}
		foreach ($this->_fileLists as $path => $files) {
			foreach ($files as $file) {
				if ($filename == $file) {
					return $path . $file;
				}
			}
		}
		throw new Exception('Could not locate file for ' . $object);
	}
/**
 * Read all the $searchPaths and cache the files inside of each.
 *
 * @return void
 **/
	protected function _readDirs() {
		foreach ($this->searchPaths as $path) {
			$this->_Folder->cd($path);
			list($dirs, $files) = $this->_Folder->read();
			$this->_fileLists[$path] = $files;
		}
	}
/**
 * Preprocess a specific file and do any nesteds inclusions that are required.
 *
 * @return string The Fully processed file
 **/
	protected function _preprocess($filename) {
		if (isset($this->_loaded[$filename])) {
			return "\n";
		}
		$this->_loaded[$filename] = true;
		$fileHandle = fopen($filename, 'r');
		while (!feof($fileHandle)) {
			$line = fgets($fileHandle);
			if (preg_match($this->requirePattern, $line, $requiredObject)) {
				if ($requiredObject[1] == '"') {
					$filename = $this->_findFile($requiredObject[2], dirname($filename) . DS);
				} else {
					$filename = $this->_findFile($requiredObject[2]);
				}
				$this->_record($this->_preprocess($filename));
			} else {
				$this->_record($line);
			}
		}
		return "\n";
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
}