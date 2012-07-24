<?php

App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * Requires lessphp to be installed.
 *
 * eg. git submodule add https://github.com/leafo/lessphp.git app/Vendor/lessphp
 *
 * @see http://leafo.net/lessphp
 */
class LessPHP extends AssetFilter {

	protected $_settings = array(
		'ext' => '.less',
		'path' => 'lessphp/lessc.inc.php',
	);

/**
 * Runs `lessc` against any files that match the configured extension.
 *
 * @param string $filename The name of the input file.
 * @param string $input The content of the file.
 * @return string
 */
	public function input($filename, $input) {
		if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
			return $input;
		}
		App::import('Vendor', 'lessc', array('file' => $this->_settings['path']));
		if (!class_exists('lessc')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'lessc'));
		}
		$lc = new lessc($filename);
		return $lc->parse();
	}

}
