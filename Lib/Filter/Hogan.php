<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Provides precompilation for mustache templates
 * with Hogan.js. Compiled templates will be inserted
 * into window.JST. The keyname of the template
 * will be the pathname without the extension, and
 * directory separators replaced with `-`.
 *
 * *Requires* the hogan.js npm module to be installed system wide.
 *
 * `npm install -g hogan.js`
 *
 * Will install hogan.
 *
 */
class Hogan extends AssetFilter {

/**
 * Settings for the filter.
 *
 * @var array
 */
	protected $_settings = array(
		'ext' => '.mustache',
		'node' => '/usr/local/bin/node',
		'node_path' => '',
	);

/**
 * Runs `hogan.compile` against all template fragments in a file.
 *
 * @param string $filename The name of the input file.
 * @param string $input The content of the file.
 * @return string
 */
	public function input($filename, $input) {
		if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
			return $input;
		}
		$tmpfile = tempnam(sys_get_temp_dir(), 'asset_compress_hogan');
		$id = str_replace($this->_settings['ext'], '', basename($filename));
		$this->_generateScript($tmpfile, $id, $input);
		$bin = $this->_settings['node'] . ' ' . $tmpfile;
		$env = array('NODE_PATH' => $this->_settings['node_path']);
		$return = $this->_runCmd($bin, '', $env);
		unlink($tmpfile);
		return $return;
	}

/**
 * Generates the javascript passed into node to precompile the
 * the mustache template.
 *
 * @param string $file The tempfile to put the script in.
 * @param string $id The template id in window.JST
 * @param string input The mustache template content.
 * @return void
 */
	protected function _generateScript($file, $id, $input) {
		$config = array(
			'asString' => true,
		);

		$text = <<<JS
var hogan = require('hogan.js'),
	util = require('util');

try {
	var template = hogan.compile(%s, %s);
	util.print('\\nwindow.JST["%s"] = ' + template + ';');
	process.exit(0);
} catch (e) {
	console.error(e);
	process.exit(1);
}
JS;
		$contents = sprintf(
			$text,
			json_encode($input),
			json_encode($config),
			$id
		);
		file_put_contents($file, $contents);
	}

}
