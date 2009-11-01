<?php

/**
 * AssetCompress Helper.
 *
 * Handle inclusion assets using the AssetCompress features for concatenating and
 * compressing asset files.
 *
 * @package asset_compress.helpers
 * @author Mark Story
 */
class AssetCompressHelper extends AppHelper {

	public $helpers = array('Html', 'Javascript');
/**
 * Options for the helper
 *
 * - `autoIncludePath` - Path inside of webroot/js that contains autoloaded view js.
 * - `jsCompressUrl` - Url to use for getting compressed js files.
 * - `cssCompressUrl` - Url to use for getting compressed css files.
 *
 * @var array
 **/
	public $options = array(
		'autoloadPath' => 'autoload',
		'cssCompressUrl' => array(
			'plugin' => 'asset_compress',
			'controller' => 'css_files',
			'action' => 'join'
		),
		'jsCompressUrl' => array(
			'plugin' => 'asset_compress',
			'controller' => 'js_files',
			'action' => 'join'
		)
	);
/**
 * Scripts to be included keyed by final filename.
 *
 * @var array
 **/
	protected $_scripts = array();
/**
 * CSS files to be included keyed by final filename.
 *
 * @var array
 **/
	protected $_css = array();
/**
 * Disable autoInclusion of view js files.
 *
 * @var string
 **/
	public $autoInclude = true;
/**
 * Set options, merge with existing options.
 *
 * @return void
 **/
	public function options($options) {
		$this->options = Set::merge($options);
	}
/**
 * BeforeRender callback, adds view js files if enabled.
 *
 * Auto file inclusion adopted from Graham Weldon's helper
 * http://bakery.cakephp.org/articles/view/automatic-javascript-includer-helper
 *
 * @return void
 **/
	public function beforeRender() {
		if (!empty($path)) {
			$path .= DS;
		}

		$files = array(
			$this->params['controller'] . '.js',
			$this->params['controller'] . DS . $this->params['action'] . '.js'
		);

		foreach ($files as $file) {
			$file = $path . $file;
			$includeFile = WWW_ROOT . 'js' . DS . $file;
			if (file_exists($includeFile)) {
				$this->Javascript->link($file, false);
			}
		}
	}
/**
 * Include a Javascript file.  All files with the same `$destination` will be compressed into one file.
 * Compression/concatenation will only occur if debug == 0.  
 * Otherwise all files will be appended to $scripts_for_layout during beforeRender.
 *
 * @param string $file Name of file to include.
 * @param string $destination Name of file that $file should be compacted into.
 * @return void
 **/
	public function javascript($file, $destination = 'default') {
		if (empty($this->_scripts[$destination]) {
			$this->_scripts[$destination] = array();
		}
		$this->_scripts[$destination][] = $file;
	}
/**
 * Include a CSS file.  All files with the same `$destination` will be compressed into one file.
 * Compression/concatenation will only occur if debug == 0.  
 * Otherwise all files will be appended to $scripts_for_layout during beforeRender.
 *
 * @param string $file Name of file to include.
 * @param string $destination Name of file that $file should be compacted into.
 * @return void
 **/
	public function css($file, $destination = 'default') {
		if (empty($this->_css[$destination]) {
			$this->_css[$destination] = array();
		}
		$this->_css[$destination][] = $file;
	}
}