<?php
App::uses('AssetScanner', 'AssetCompress.Lib');
App::uses('AssetFilterCollection', 'AssetCompress.Lib');

/**
 * Compiles a set of assets together, and applies filters.
 * Forms the center of AssetCompress
 *
 * @package asset_compress
 */
class AssetCompiler {

	protected $_Config;

	public function __construct(AssetConfig $config) {
		$this->_Config = $config;
	}

/**
 * Generate a compiled asset, with all the configured filters applied.
 *
 * @param string $target The name of the build target to generate.
 * @return The processed result of $target and it dependencies.
 * @throws RuntimeException
 */
	public function generate($build) {
		$ext = $this->_Config->getExt($build);
		$this->_Scanner = $this->_makeScanner($this->_Config->paths($ext), $this->_Config->theme());
		$this->filters = $this->_makeFilters($ext, $build);

		$output = '';
		$files = $this->_Config->files($build);
		if (empty($files)) {
			throw new RuntimeException(sprintf('No files found for build file "%s"', $build));
		}
		foreach ($files as $file) {
			$file = $this->_findFile($file);
			$content = $this->_readFile($file);
			$content = $this->filters->input($file, $content);
			$output .= $content;
		}
		if (Configure::read('debug') < 2 || php_sapi_name() == 'cli') {
			$output = $this->filters->output($build, $output);
		}
		return trim($output);
	}

/**
 * Factory method for AssetScanners
 *
 * @param array $paths The paths the scanner should be reading.
 * @param string $theme The active theme if there is one
 * @return AssetScanner
 */
	protected function _makeScanner($paths, $theme) {
		return new AssetScanner($paths, $theme);
	}

/**
 * Factory method for AssetFilterCollection
 *
 * @param string $ext The extension An array of filters to put in the collection
 */
	protected function _makeFilters($ext, $target) {
		$config = array(
			'paths' => $this->_Config->paths($ext),
			'target' => $target
		);
		$filters = $this->_Config->filters($ext, $target);
		$filterSettings = $this->_Config->filterConfig($filters);
		return new AssetFilterCollection($filters, $config, $filterSettings);
	}

/**
 * Locate a file using the Scanner.
 *
 * @return string The full filepath to the file.
 * @throws Exception when files can't be found.
 */
	protected function _findFile($object) {
		$filename = $this->_Scanner->find($object);
		if (!$filename) {
			throw new Exception(sprintf('Could not locate file "%s"', $object));
		}
		return $filename;
	}

/**
 * Reads the asset file and returns the contents.
 *
 * @param string $file The filename
 * @return string The contents of $file.
 */
	protected function _readFile($file) {
		$content = '';
		if ($this->_Scanner->isRemote($file)) {
			$handle = @fopen($file, 'rb');
			if ($handle) {
				$content = stream_get_contents($handle);
				fclose($handle);
			}
		} else {
			$content = file_get_contents($file);
		}
		return $content;
	}

}
