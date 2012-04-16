<?php
App::uses('AssetFilter', 'AssetCompress.Lib');
App::uses('AssetScanner', 'AssetCompress.Lib');

/**
 * A preprocessor that inlines files referenced by
 * @import() statements in css files.
 *
 * @package asset_compress
 */
class ImportInline extends AssetFilter {

	protected $_pattern = '/^\s*@import\s*(?:(?:([\'"])([^\'"]+)\\1)|(?:url\(([\'"])([^\'"]+)\\3\)));/m';

	protected $_Scanner = null;

	protected $_loaded = array();

	public function settings($settings) {
		parent::settings($settings);
		$this->_Scanner = new AssetScanner($settings['paths']);
	}

/**
 * Preprocesses CSS files and replaces @import statements.
 *
 * @param string $filename
 * @param string $content
 * @return The processed file.
 */
	public function input($filename, $content) {
		return preg_replace_callback(
			$this->_pattern,
			array($this, '_replace'),
			$content
		);
	}

/**
 * Does file replacements.
 *
 * @param array $matches
 * @throws RuntimeException
 */
	protected function _replace($matches) {
		$required = empty($matches[2]) ? $matches[4] : $matches[2];
		$filename = $this->_Scanner->find($required);
		if (!$filename) {
			throw RuntimeException(sprintf('Could not find dependency "%s"', $required));
		}
		if (empty($this->_loaded[$filename])) {
			return $this->input($filename, file_get_contents($filename));
		}
		return '';
	}

}
