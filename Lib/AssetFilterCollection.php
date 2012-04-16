<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

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
	public function __construct(array $filters, array $config, array $filterSettings) {
		$this->_config = $config;
		$this->_buildFilters($filters, $filterSettings);
	}

/**
 * Builds the filters in the collection.
 *
 * @throws Exception
 */
	protected function _buildFilters($filters, $settings) {
		foreach ($filters as $className) {
			list($plugin, $className) = pluginSplit($className, true);
			App::import('Lib', $plugin . 'asset_compress/filter/' . $className);
			if (!class_exists($className)) {
				App::import('Lib', 'AssetCompress.filter/' . $className);
				if (!class_exists($className)) {
					throw new Exception(sprintf('Cannot not load filter "%s".', $className));
				}
			}
			$config = array_merge($this->_config, isset($settings[$className]) ? $settings[$className] : array());
			$filter = new $className();
			$this->addFilter($filter);
			$filter->settings($config);
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

/**
 * Check to see if the Collection contains a filter with
 * a specific classname.
 *
 * @param string $name The classname you want to check.
 * @return boolean.
 */
	public function has($name) {
		try {
			$this->get($name);
			return true;
		} catch (RuntimeException $e) {
			return false;
		}
	}

/**
 * Get a filter with a specific classname
 *
 * @param string $name The name of the class you want.
 * @return AssetFilterInterface An asset filter.
 * @throws RuntimeException
 */
	public function get($name) {
		foreach ($this->_filters as $filter) {
			if ($filter instanceof $name) {
				return $filter;
			}
		}
		throw new RuntimeException('Could not fetch filter ' . $name);
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
