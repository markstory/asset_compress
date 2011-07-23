<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');

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
 * Where JSMin can be found.
 *
 * @var array
 */
	protected $_settings = array(
		'path' => 'jsmin/jsmin.php'
	);

/**
 * Apply JsMin to $content.
 *
 * @param string $filename
 * @param string $content Content to filter.
 * @return string
 */
	public function output($filename, $content) {
		App::import('Vendor', 'jsmin', array('file' => $this->_settings['path']));
		return JsMin::minify($content);
	}
}
