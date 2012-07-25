<?php
App::uses('AssetFilter', 'AssetCompress.Lib');
App::uses('AssetScanner', 'AssetCompress.Lib');

/**
 * Implements directive replacement similar to sprockets <http://getsprockets.org>
 * Does not implement the //= provides syntax.
 *
 * @package asset_compress
 */
class Sprockets extends AssetFilter {

	protected $_Scanner;

/**
 * Regex pattern for finding //= require <file> and //= require "file" style inclusions
 *
 * @var stgin
 */
	protected $_pattern = '/^\s?\/\/\=\s+require\s+([\"\<])([^\"\>]+)[\"\>][\r]\n+/m';

/**
 * A list of unique files already processed.
 *
 * @var array
 */
	protected $_loaded = array();

/**
 * The current file being processed, used for "" file inclusion.
 *
 * @var string
 */
	protected $_currentFile = '';

/**
 * Configure paths for Sprockets
 *
 * @param array $settings.
 * @return void
 */
	public function settings($settings) {
		parent::settings($settings);
		$this->_Scanner = new AssetScanner($settings['paths']);
	}

/**
 * Input filter - preprocesses //=require statements
 *
 * @param string $filename
 * @param string $content
 * @return string $content
 */
	public function input($filename, $content) {
		$this->_currentFile = $filename;
		return preg_replace_callback(
			$this->_pattern,
			array($this, '_replace'),
			$content
		);
	}

/**
 * Performs the replacements and inlines dependencies.
 *
 * @param array $matches
 * @return string $content
 */
	protected function _replace($matches) {
		$file = $this->_currentFile;
		if ($matches[1] == '"') {
			// Same directory include
			$file = $this->_findFile($matches[2], dirname($file) . DS);
		} else {
			// scan all paths
			$file = $this->_findFile($matches[2]);
		}

		// prevent double inclusion
		if (isset($this->_loaded[$file])) {
			return "";
		}
		$this->_loaded[$file] = true;

		$content = file_get_contents($file);
		if ($return = $this->input($file, $content)) {
			return $return . "\n";
		}
		return '';
	}

/**
 * Locates sibling files, or uses AssetScanner to locate <> style dependencies.
 *
 * @param string $file The basename of the file needing to be found.
 * @param string $path The path for same directory includes.
 * @return string Path to file.
 * @throws Exception when files can't be located.
 */
	protected function _findFile($file, $path = null) {
		if (substr($file, -2) != 'js') {
			$file .= '.js';
		}
		if ($path && file_exists($path . $file)) {
			return $path . $file;
		}
		$file = $this->_Scanner->find($file);
		if ($file) {
			return $file;
		}
		throw new Exception('Sprockets - Could not locate ' . $file);
	}
}
