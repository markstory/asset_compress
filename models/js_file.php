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
 * Extension value, used with validExtension()
 *
 * @var string
 */
	protected $_extension = 'js';

/**
 * pattern for finding dependancies.
 *
 * matches:
 *
 * - //= require "foo"
 * - //= require <foo>
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
		$filename = $object;
		if (substr($filename, -3) != '.js') {
			$filename .= '.js';
		}
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
				if (strpos($filename, '/') !== false && file_exists($path . str_replace('/', DS, $filename))) {
					return $path . $filename;
				}
			}
		}
		throw new Exception('Could not locate file for ' . $object);
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
		$blockCommentEnd = '#^.*\*+/.*#s';

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