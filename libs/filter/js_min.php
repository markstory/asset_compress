<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');
App::import('Vendor', 'JsMin', array('file' => 'JSMin/JSMin.php'));

/**
 * JsMin filter.
 *
 * Allows you to filter Javascript files through JsMin.  You need to put JsMin in your application's
 * vendors directories.  You can get it from http://github.com/rgrove/jsmin-php/
 *
 * @package asset_compress
 * @author Mark Story
 */
class JsMinFilter implements AssetFilterInterface {
/**
 * Apply JsMin to $content.
 *
 * @param string $content Content to filter.
 * @return string
 */
	public function filter($content) {
		return JsMin::minify($content);
	}
}