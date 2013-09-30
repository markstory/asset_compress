<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * JsMin filter.
 *
 * Allows you to filter Javascript files through JsMin. You need to put JsMin in your application's
 * vendors directories. You can get it from http://github.com/rgrove/jsmin-php/
 *
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
 * @throws Exception
 * @return string
 */
	public function output($filename, $content) {
		App::import('Vendor', 'jsmin', array('file' => $this->_settings['path']));
		if (!class_exists('JsMin')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'JsMin'));
		}
		return JsMin::minify($content);
	}
}
