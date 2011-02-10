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
 */
App::import('Model', 'AssetCompress.AssetCompressor');

class CssFile extends AssetCompressor {
/**
 * config file key name
 *
 * @var string
 */
	protected $_configKeyName = 'Css';

/**
 * Extension value, used with validExtension()
 *
 * @var string
 */
	protected $_extension = 'css';

/**
 * Preprocess a specific file and do any nesteds inclusions that are required.
 *
 * @param string $filename Name of the file to load and preprocess
 * @return string The Fully processed file, or "\n" if in a recursive call
 */
	protected function _preprocess($filename) {
		if (is_array($filename)) {
			$required = empty($filename[2]) ? $filename[4] : $filename[2];
			$filename = $this->_findFile($required);
			return $this->_preprocess($filename);
		}
		$pattern = '/^\s*@import\s*(?:(?:([\'"])([^\'"]+)\\1)|(?:url\(([\'"])([^\'"]+)\\3\)));/m';
		return preg_replace_callback($pattern, array($this, '_preprocess'), parent::_preprocess($filename));
	}

/**
 * For BC compatibility.
 *
 * @return void
 */
	protected function _applyFilters() {
		if ($this->settings['stripComments']) {
			array_unshift($this->settings['filters'], 'CssStripComments');
		}
		return parent::_applyFilters();
	}

}