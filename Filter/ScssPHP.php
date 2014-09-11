<?php

App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Pre-processing filter that adds support for SCSS files.
 *
 * Requires scssphp to be installed.
 *
 * eg. git submodule add https://github.com/leafo/scssphp.git app/Vendor/scssphp
 *
 * @see http://leafo.net/scssphp
 */
class ScssPHP extends AssetFilter {

	protected $_settings = array(
		'ext' => '.scss',
		'path' => 'scssphp/scss.inc.php',
	);

/**
 * Runs `scssc` against any files that match the configured extension.
 *
 * @param string $filename The name of the input file.
 * @param string $input The content of the file.
 * @throws Exception
 * @return string
 */
	public function input($filename, $input) {
		if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
			return $input;
		}
		App::import('Vendor', 'scssc', array('file' => $this->_settings['path']));
		if (!class_exists('scssc')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'scssc'));
		}
		$sc = new scssc();
		$sc->addImportPath(dirname($filename));
		return $sc->compile($input);
	}

}
