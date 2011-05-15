<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
/**
 * A collection for creating and interacting with filter sets.
 *
 *
 * @package asset_compress
 */
class AssetFilterCollection {
	protected $_config = array();

	protected $_filters = array();

/**
 * Construct a filter collection.
 *
 * @param array $filters An array of filters as keys, with their settings as the values.
 * @param array $config An array of global settings for all filters. Contains 'paths'
 */
	function __construct(array $filters, array $config) {
		$this->_config = $config;
		$this->_buildFilters($filters);	
	}

	protected function _buildFilters($filters) {
		foreach ($filters as $className) {
			list($plugin, $className) = pluginSplit($className, true);
			App::import('Lib', $plugin . 'asset_compress/filter/' . $className);
			if (!class_exists($className)) {
				App::import('Lib', 'AssetCompress.filter/' . $className);
				if (!class_exists($className)) {
					throw new Exception(sprintf('Cannot not load filter "%s".', $className));
				}
			}
			$filter = new $className();
			$this->addFilter($filter);
			$filter->settings($this->_config);
		}
	}

/**
 * Append a filter to the list of filters in the collection.
 *
 * @param AssetFilterInterface $filter The filter to append.
 */
	public function addFilter(AssetFilterInterface $filter) {
		$this->_filters[] = $filter;
	}

	public function has($name) {
		foreach ($this->_filters as $filter) {
			if ($filter instanceof $name) {
				return true;
			}
		}
		return false;
	}

/**
 * Apply all the input filters in sequence to the file and content.
 *
 * @param string $file Filename being processed.
 * @param string $content The content of the file.
 * @return string The content with all input filters applied.
 */
	public function input($file, $content) {
		foreach ($this->_filters as $filter) {
			$content = $filter->input($file, $content);
		}
		return $content;
	}
	
/**
 * Apply all the output filters in sequence to the file and content.
 *
 * @param string $file Filename being processed.
 * @param string $content The content of the file.
 * @return string The content with all output filters applied.
 */
	public function output($target, $content) {
		foreach ($this->_filters as $filter) {
			$content = $filter->output($target, $content);
		}
		return $content;
	}
}
