<?php
namespace AssetCompress\Filter;

use lessc;

use AssetCompress\AssetFilter;
/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * Requires lessphp to be installed via composer.
 *
 * @see http://leafo.net/lessphp
 */
class LessPHP extends AssetFilter {

	protected $_settings = array(
		'ext' => '.less',
	);

/**
 * Runs `lessc` against any files that match the configured extension.
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
		if (!class_exists('lessc')) {
			throw new \Exception(sprintf('Cannot not load filter class "%s".', 'lessc'));
		}
		$lc = new lessc($filename);
		return $lc->parse();
	}

}
