<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Pre-processing filter that adds support for SCSS files.
 *
 * Requires nodejs and scss to be installed.
 *
 * @see http://sass-lang.com/
 */
class ScssFilter extends AssetFilter {

	protected $_settings = array(
		'ext' => '.scss',
		'node' => '/usr/local/bin/node',
		'node_path' => '/usr/local/lib/node_modules'
	);

/**
 * Runs SCSS compiler against any files that match the configured extension.
 *
 * @param string $filename The name of the input file.
 * @param string $input The content of the file.
 * @return string
 */
	public function input($filename, $input) {
		if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
			return $input;
		}

		$tmpfile = tempnam(sys_get_temp_dir(), 'asset_compress_scss');
		$this->_generateScript($tmpfile, $filename);

		$bin = $this->_settings['node'] . ' ' . $tmpfile;
		$env = array('NODE_PATH' => $this->_settings['node_path']);
		$return  = $this->_runCmd($bin, '', $env);
		unlink($tmpfile);		
		return $return;
	}

	protected function _generateScript($file, $input) {
		$text = <<<JS
var compiler = require('scss/src/compiler'),
	sys = require('sys'),
	fs = require('fs');


fs.readFile('%s', function(err, scssFile) {
	compiler.compile(scssFile.toString(), function(err, css) {
		if(err) {
	    	sys.puts(sys.inspect(err));
		} else {
			sys.puts(css);
		}
	});
});
JS;
		file_put_contents($file, sprintf($text, $input));
	}
}
