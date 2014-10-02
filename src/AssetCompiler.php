<?php
namespace AssetCompress;

use AssetCompress\AssetFilterCollection;
use AssetCompress\AssetScanner;
use Cake\Core\Configure;
use RuntimeException;

/**
 * Compiles a set of assets together, and applies filters.
 * Forms the center of AssetCompress
 *
 */
class AssetCompiler {

/**
 * Instance of AssetFilterCollection
 *
 * @var AssetFilterCollection
 */
	protected $_filterCollection;

/**
 * Instance of AssetConfig
 *
 * @var AssetConfig
 */
	protected $_config;

/**
 * The file list associated with a build.
 *
 * @var array
 */
	protected $_fileList = array();

/**
 * Constructor.
 *
 * @param AssetConfig $config Configuration object.
 * @return void
 */
	public function __construct(AssetConfig $config) {
		$this->_config = $config;
	}

/**
 * Generate a compiled asset, with all the configured filters applied.
 *
 * @param string $target The name of the build target to generate.
 * @return The processed result of $target and it dependencies.
 * @throws RuntimeException
 */
	public function generate($build) {
		$this->_fileList = array();
		$output = '';
		foreach ($this->_getFilesList($build) as $file) {
			$content = $this->_readFile($file);
			$content = $this->_filterCollection->input($file, $content);
			$output .= $content . "\n";
		}
		if (!Configure::read('debug') || php_sapi_name() === 'cli') {
			$output = $this->_filterCollection->output($build, $output);
		}
		return trim($output);
	}

/**
 * Gets the latest modified time for the files set on the build
 *
 * @param string $target The name of the build target to generate.
 * @return integer last modified time in UNIX seconds
 */
	public function getLastModified($build) {
		$time = 0;
		foreach ($this->_getFilesList($build) as $file) {
			if ($this->_scanner->isRemote($file)) {
				return time();
			}
			$mtime = filemtime($file);
			$time = ($mtime > $time) ? $mtime : $time;
		}
		return $time;
	}

/**
 * Returns the list of files required to generate a named build
 *
 * @param string $target The name of the build target to generate.
 * @return array The list of files to be processed
 * @throws RuntimeException
 */
	protected function _getFilesList($build) {
		if (!empty($this->_fileList[$build])) {
			return $this->_fileList[$build];
		}
		$ext = $this->_config->getExt($build);
		$this->_scanner = $this->_makeScanner($this->_config->paths($ext, $build), $this->_config->theme());
		$this->_filterCollection = $this->_makeFilters($ext, $build);

		$output = '';
		$files = $this->_config->files($build);
		if (empty($files)) {
			throw new RuntimeException(sprintf('No files found for build file "%s"', $build));
		}

		foreach ($files as &$file) {
			$file = $this->_findFile($file);
		}
		return $this->_fileList[$build] = $files;
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
 * @return FilterCollection
 */
	protected function _makeFilters($ext, $target) {
		$config = array(
			'paths' => $this->_config->paths($ext, $target),
			'target' => $target,
			'theme' => $this->_config->theme()
		);
		$filters = $this->_config->filters($ext, $target);
		$filterSettings = $this->_config->filterConfig($filters);
		return new AssetFilterCollection($filters, $config, $filterSettings);
	}

/**
 * Locate a file using the Scanner.
 *
 * @return string The full filepath to the file.
 * @throws Exception when files can't be found.
 */
	protected function _findFile($object) {
		$filename = $this->_scanner->find($object);
		if (!$filename) {
			throw new RuntimeException(sprintf('Could not locate file "%s"', $object));
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
		if ($this->_scanner->isRemote($file)) {
			$handle = fopen($file, 'rb');
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
