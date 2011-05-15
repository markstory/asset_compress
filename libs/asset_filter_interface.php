<?php
/**
 * AssetFilterInterface all filters declared in your config.ini must implement 
 * this interface or exceptions will be thrown.
 *
 * @package asset_compress
 */
interface AssetFilterInterface {
/**
 * Input filters are used to do pre-processing on each file in a 
 * build target.
 *
 * @param string $filename Name of the file
 * @param string $content Content of the file.
 */
	public function input($filename, $content);

/**
 * Output filters are used to do minification or do other manipulation
 * on the content before $targetFile is saved/output.
 *
 * @param string $target The build target being made.
 * @param string $content The content to filter.
 */
	public function output($targetFile, $content);

/**
 * Gets settings for this filter.  Will always include 'paths'
 * key which points at paths available for the type of asset being generated.
 *
 * @param array $settings Array of settings.
 */
	public function settings($settings);
}


/**
 * A simple base class you can build filters on top of 
 * if you only want to implement either input() or output()
 *
 * @package asset_compress
 */
class AssetFilter implements AssetFilterInterface {

/**
 * Settings
 *
 * @var array
 */
	protected $_settings = array();

/**
 * Gets settings for this filter.  Will always include 'paths'
 * key which points at paths available for the type of asset being generated.
 *
 * @param array $settings Array of settings.
 */
	public function settings($settings) {
		$this->_settings = $settings;
	}

/**
 * Input filter.
 *
 * @param string $filename Name of the file
 * @param string $content Content of the file.
 */
	public function input($filename, $content) {
		return $content;
	}

/**
 * Output filter.
 *
 * @param string $target The build target being made.
 * @param string $content The content to filter.
 */
	public function output($target, $content) {
		return $content;
	}
}
