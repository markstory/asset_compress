<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
App::import('Vendor', 'cssmin', array('file' => 'cssmin/CssMin.php'));

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
 * Apply CssMin to $content.
 *
 * @param string $filename target filename
 * @param string $content Content to filter.
 * @return string
 */
	public function output($filename, $content) {
		return CssMin::minify($content);
	}
}
