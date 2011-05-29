<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
App::import('Vendor', 'jsmin', array('file' => 'jsmin/jsmin.php'));

/**
 * JsMin filter.
 *
 * Allows you to filter Javascript files through JsMin.  You need to put JsMin in your application's
 * vendors directories.  You can get it from http://github.com/rgrove/jsmin-php/
 *
 * @package asset_compress
 */
class JsMinFilter extends AssetFilter {
/**
 * Apply JsMin to $content.
 *
 * @param string $filename
 * @param string $content Content to filter.
 * @return string
 */
	public function output($filename, $content) {
		return JsMin::minify($content);
	}
}
