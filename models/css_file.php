<?php
/**
 * CSS File Preprocessor model.
 * Preprocesss CSS files for @import() statements. All @import() statements will be
 * inlined, making one larger CSS file.
 * 
 * Uses Plugin config.ini file for path and other directive information.
 *
 * @package asset_compress
 * @author Mark Story
 **/
App::import('Model', 'AssetCompress.AssetCompressor');

class CssFile extends AssetCompressor {
/**
 * config file key name
 *
 * @var string
 **/
	protected $_configKeyName = 'Css';

/**
 * Extension value, used with validExtension()
 *
 * @var string
 */
	protected $_extension = 'css';

/**
 * pattern for finding @import.
 *
 * @var string
 **/
	public $importPattern = '/^\s*@import\s*(?:(?:([\'"])([^\'"]+)\\1)|(?:url\(([\'"])([^\'"]+)\\3\)))/';

/**
 * Scan each of the $searchPaths for the named object / filename
 *
 * @return string Full path to the $object
 **/
	protected function _findFile($object, $path = null) {
		$filename = $object;
		if (substr($filename, -4) != '.css') {
			$filename .= '.css';
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
			if (preg_match($this->importPattern, $line, $requiredObject)) {
				$required = empty($requiredObject[2]) ? $requiredObject[4] : $requiredObject[2];
				$filename = $this->_findFile($required);
				$this->_record($this->_preprocess($filename));
			} else {
				$this->_record($line);
			}
		}
		$this->_record("\n");
		return '';
	}

/**
 * Remove comments in a line.
 *
 * @return string code line with no comments
 **/
	protected function _stripComments($line) {
		$blockCommentLine = '#^\s*/\*+.*\*+/#s';
		$blockCommentStart = '#^\s*/\*+(?!!).*#s';
		$blockCommentEnd = '#^.*\*+/.*#s';
		if ($this->_inCommentBlock) {
			if (preg_match($blockCommentEnd, $line)) {
				$this->_inCommentBlock = false;
			}
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