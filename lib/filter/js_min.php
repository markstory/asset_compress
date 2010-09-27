<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');
App::import('Vendor', 'JsMin', array('file' => 'JSMin/JSMin.php'));

/**
 * JsMin filter.
 *
 * Allows you to filter Javascript files through JsMin.  You need to put JsMin in your application's
 * 
 *
 * @package default
 * @author Mark Story
 */
class JsMinFilter implements AssetFilterInterface {
/**
 * Apply JsMin to $content.
 *
 * @return void
 */
	public function filter($content) {
		return JsMin::minify($content);
	}
}