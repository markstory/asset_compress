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
 * Tracks file processing.
 *
 * @var array
 */
	protected $_files = array();

/**
 * Preprocess a specific file and do any nesteds inclusions that are required.
 *
 * @param string $filename Name of the file to load and preprocess
 * @return string The Fully processed file, or "\n" if in a recursive call
 **/
	protected function _preprocess($filename) {
		if (is_array($filename)) {
			$file = array_pop($this->_files);
			if ($filename[1] == '"') {
				$filename = $this->_findFile($filename[2], dirname($file) . DS);
			} else {
				$filename = $this->_findFile($filename[2]);
			}
			if ($return = $this->_preprocess($filename)) {
				return $return . "\n";
			}
			return $return;
		}
		$this->_files[] = $filename;
		$pattern = '/^\s?\/\/\=\s+require\s+([\"\<])([^\"\>]+)[\"\>]\n+/m';
		return preg_replace_callback($pattern, array($this, '_preprocess'), parent::_preprocess($filename));
	}

/**
 * For BC compatibility.
 *
 * @return void
 */
	protected function _applyFilters() {
		if ($this->settings['stripComments']) {
			array_unshift($this->settings['filters'], 'JsStripComments');
		}
		return parent::_applyFilters();
	}
}