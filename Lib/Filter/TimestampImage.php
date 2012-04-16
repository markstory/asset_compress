<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Adds timestamp querystrings to all background images in CSS files.
 * This helps with cachebusting CSS sprites.  This is useful in 
 * development, and deployment to ensure you always have the most recent 
 * images.
 *
 * @package asset_compress
 */
class TimestampImage extends AssetFilter {

/**
 * Regex for `background` CSS property.
 *
 * @var string
 */
	protected $_backgroundPattern = '/^(?<prop>.*background\s*\:\s*(?:\#[a-f0-9A-F]{3,6})?\s*url\([\'"]?)(?<path>[^\'")]+?(?:png|gif|jpg))(?<trail>[\'"]?\))/m';

/**
 * Regex for `background-image` CSS property.
 *
 * @var string
 */
	protected $_backgroundImagePattern = '/^(?<prop>.*background-image\s*\:\s*url\([\'"]?)(?<path>[^\'")]+?(?:png|gif|jpg))(?<trail>[\'"]?\))/m';

	protected $_filename;

/**
 * Input filter.  Locates CSS background images relative to the 
 * filename and gets the filemtime for the images.
 *
 * @param string $filename The file being processed
 * @param string $content The file content
 * @return The content with images timestamped.
 */
	public function input($filename, $content) {
		$this->_filename = $filename;
		$content = preg_replace_callback($this->_backgroundPattern, array($this, '_replace'), $content);
		$content = preg_replace_callback($this->_backgroundImagePattern, array($this, '_replace'), $content);
		return $content;
	}

/**
 * Do replacements.
 *
 * - $matches[0] -> whole background line.
 * - $matches[path] -> the url with any wrapping '/'
 *
 * If the image path starts with / its assumed to be an absolute path 
 * which will be prepended with WWW_ROOT
 *
 * @param array $matches Array of matches
 * @return string Replaced code.
 */
	protected function _replace($matches) {
		$path = $matches['path'];
		if ($path[0] == '/') {
			$imagePath = WWW_ROOT . rtrim($path, '/');
		} else {
			$imagePath = realpath(dirname($this->_filename) . DS . $path);
		}
		if (file_exists($imagePath)) {
			$path = $this->_timestamp($imagePath, $path);
		}
		return $matches['prop'] . $path . $matches['trail'];
	}

/**
 * Add timestamps to the given path.  Will not change paths with 
 * querystrings, as they could have anything in them or be customized 
 * already.
 *
 * @param string $filepath The absolute path to the file for timestamping
 * @param string $path The path to append a timestamp to.
 * @return string Path with a timestamp.
 */
	protected function _timestamp($filepath, $path) {
		if (strpos($path, '?') === false) {
			$path .= '?t=' . filemtime($filepath);
		}
		return $path;
	}

}
