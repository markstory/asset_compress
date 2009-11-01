<?php
/**
 * Resource compressor base class for File compacting models.
 *
 */
abstract class AssetCompressor extends Object {
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
}
