<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
App::import('Lib', 'AssetCompress.AssetScanner');

/**
 * Implements directive replacement similar to sprockets <http://getsprockets.org>
 *
 * @package asset_compress
 */
class Sprockets extends AssetFilter {

	protected $_Scanner;
	protected $_pattern = '/^\s?\/\/\=\s+require\s+([\"\<])([^\"\>]+)[\"\>]\n+/m';

	public function settings($settings) {
		parent::settings($settings);
		$this->_Scanner = new AssetScanner($settings['paths']);
	}

/**
 * Input filter - preprocesses //=require statements
 *
 * @param string $filename
 * @param string $content
 */
	public function input($filename, $content) {
		$this->_files[] = $filename;
		return preg_replace_callback(
			$this->_pattern,
			array($this, '_replace'), 
			$content
		);
	}

	protected function _replace($matches) {
		$file = array_pop($this->_files);
		if ($matches[1] == '"') {
			// Same directory include
			$file = $this->_findFile($matches[2], dirname($file) . DS);
		} else {
			// scan all paths
			$file = $this->_findFile($matches[2]);
		}
		$content = file_get_contents($file);
		if ($return = $this->input($file, $content)) {
			return $return . "\n";
		}
		return '';
	}

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
		throw new Exception('Could not locate ' . $file);
	}
}
