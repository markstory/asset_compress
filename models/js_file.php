<?php
/**
 * Javascript File Preprocessor model.
 * Preprocesss JS files for //= require statements.
 * 
 * Implements many of the same features that Sprockets does, and was directly 
 * inspired by Sprockets for ruby by Sam Stephenson <http://conio.net/>
 * and <http://getsprockets.org/>˝
 * 
 * Uses Plugin config.ini file for path and other directive information.
 *
 * @package asset_compress
 * @author Mark Story
 **/
App::import('Model', 'AssetCompress.AssetCompressor');

class JsFile extends AssetCompressor {
/**
 * Key name for the config file.
 *
 * @var string
 **/
	protected $_configKeyName = 'Javascript';
/**
 * Properties to set from the config file.
 *
 * @var array
 **/
	protected $_configProperties = array('stripComments', 'searchPaths', 'requirePattern');
/**
 * pattern for finding dependancies.
 *
 * @var string
 **/
	public $requirePattern = '/^\s?\/\/\=\s+require\s+([\"\<])([^\"\>]+)[\"\>]/';
/**
 * Scan each of the $searchPaths for the named object / filename
 *
 * @return string Full path to the $object
 **/
	protected function _findFile($object, $path = null) {
		$filename = Inflector::underscore($object) . '.js';
		if ($path !== null) {
			return $path . $filename;
		}
		if (empty($this->_fileLists)) {
			$this->_readDirs();
		}
		foreach ($this->_fileLists as $path => $files) {
			foreach ($files as $file) {
				if ($filename == $file) {
					return $path . $file;
				}
				if (strpos($filename, DS) !== false && file_exists($path . $filename)) {
					return $path . $filename;
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
 * Preprocess a specific file and do any nesteds inclusions that are required.
 *
 * @param string $filename Name of the file to load and preprocess
 * @return string The Fully processed file, or "\n" if in a recursive call
 **/
	protected function _preprocess($filename) {
		if (isset($this->_loaded[$filename])) {
			return '';
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
		$this->_record("\n");
		return '';
	}
/**
 * Remove // Comments in a line.
 *
 * @return string code line with no comments
 **/
	protected function _stripComments($line) {
		$inlineComment = '#^\s*//.*$#s';
		$blockCommentLine = '#^\s*/\*+.*\*+/#s';
		$blockCommentStart = '#^\s*/\*+(?!!).*#s';
		$blockCommentEnd = '#^\s*\*+/.*#s';

		if ($this->_inCommentBlock) {
			if (preg_match($blockCommentEnd, $line)) {
				$this->_inCommentBlock = false;
			}
			return '';
		}
		if (preg_match($inlineComment, $line)) {
			return '';
		}
		if (preg_match($blockCommentLine, $line)) {
			return '';
		}
		if (preg_match($blockCommentStart, $line)) {
			$this->_inCommentBlock = true;
			return '';
		}
		return $line;
	}
}