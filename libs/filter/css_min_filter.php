<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');

/**
 * CssMin filter.
 *
 * Allows you to filter Css files through CssMin.  You need to put CssMin in your application's
 * vendors directories.  You can get it from http://code.google.com/p/cssmin/
 *
 * @package asset_compress
 */
class CssMinFilter extends AssetFilter {
/**
 * Where CssMin can be found.
 *
 * @var array
 */
	protected $_settings = array(
		'path' => 'cssmin/CssMin.php'
	);

/**
 * Apply CssMin to $content.
 *
 * @param string $filename target filename
 * @param string $content Content to filter.
 * @return string
 */
	public function output($filename, $content) {
		App::import('Vendor', 'cssmin', array('file' => $this->_settings['path']));
		return CssMin::minify($content);
	}
}
