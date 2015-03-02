<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * JsMin filter.
 *
 * Allows you to filter Javascript files through JSMin. You need either the
 * `jsmin` PHP extension installed, or a copy of `jsmin-php` in one of your
 * application's `vendors` directories.
 *
 * @link https://github.com/sqmk/pecl-jsmin
 * @link http://github.com/rgrove/jsmin-php
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
 * Apply JSMin to $content.
 *
 * @param string $filename
 * @param string $content Content to filter.
 * @throws Exception
 * @return string
 */
	public function output($filename, $content) {
		if (function_exists('jsmin')) {
			return jsmin($content);
		}
		App::import('Vendor', 'jsmin', array('file' => $this->_settings['path']));
		if (!class_exists('JSMin')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'JSMin'));
		}
		return JSMin::minify($content);
	}
}
