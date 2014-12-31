<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * JSMin filter.
 *
 * Allows you to filter Javascript files through JSMin. You need to put JSMin in your application's
 * vendors directories. You can get it from http://github.com/rgrove/jsmin-php/
 *
 */
class JSMinFilter extends AssetFilter {

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
		App::import('Vendor', 'jsmin', array('file' => $this->_settings['path']));
		if (!class_exists('JSMin')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'JSMin'));
		}
		return JSMin::minify($content);
	}
}
