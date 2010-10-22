<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');
App::import('Vendor', 'CssMin', array('file' => 'CssMin/CssMin.php'));

/**
 * CssMin filter.
 *
 * Allows you to filter Css files through CssMin.  You need to put CssMin in your application's
 * vendors directories.  You can get it from http://code.google.com/p/cssmin/
 *
 * @package asset_compress
 * @author Mark Story
 */
class CssMinFilter implements AssetFilterInterface {
/**
 * Apply CssMin to $content.
 *
 * @param string $content Content to filter.
 * @return string
 */
	public function filter($content) {
		return CssMin::minify($content);
	}
}