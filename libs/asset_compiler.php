<?php
App::import('Libs', 'AssetCompress.AssetScanner');

/**
 * Compiles a set of assets together, and applies filters.
 * Forms the center of AssetCompress
 *
 * @package asset_compress
 */
class AssetCompiler {

	protected $_Config;

	function __construct(AssetConfig $config) {
		$this->_Config = $config;
	}

/**
 * Generate a compiled asset, with all the configured filters applied.
 *
 * @param string $target The name of the build target to generate.
 * @return The processed result of $target and it dependencies. 
 */
	public function generate($build) {
		$ext = $this->_Config->getExt($build);
		$this->_Scanner = new AssetScanner($this->_Config->paths($ext));
		//$this->filters = new AssetFilterCollection($this->_Config, $ext, $build);

		$output = '';
		$files = $this->_Config->files($build);
		foreach ($files as $file) {
			$file = $this->_findFile($file);
			$content = file_get_contents($file);
			// $content = $this->filters->input($file, $content);
			$output .= $content;
		}
		// $content = $this->filters->output($file, $content);
		return trim($output);
	}

	protected function _findFile($object) {
		$filename = $this->_Scanner->find($object);
		if (!$filename) {
			throw new Exception(sprintf('Could not locate file "%s"', $object));
		}
		return $filename;
	}
}
