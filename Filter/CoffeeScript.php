<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Pre-processing filter that adds support for CoffeeScript files.
 *
 * Requires both nodejs and CoffeeScript to be installed.
 *
 * @see http://jashkenas.github.com/coffee-script/
 */
class CoffeeScript extends AssetFilter {

	protected $_settings = array(
		'ext' => '.coffee',
		'coffee' => '/usr/local/bin/coffee',
		'node' => '/usr/local/bin/node',
		'node_path' => '/usr/local/lib/node_modules'
	);

/**
 * Runs `coffee` against files that match the configured extension.
 *
 * @param string $filename Filename being processed.
 * @param string $content Content of the file being processed.
 * @return string
 */
	public function input($filename, $input) {
		if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
			return $input;
		}
		$cmd = $this->_settings['node'] . ' ' . $this->_settings['coffee'] . ' -c -p -s ';
		$env = array('NODE_PATH' => $this->_settings['node_path']);
		return $this->_runCmd($cmd, $input, $env);
	}
}
