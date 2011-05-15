<?php
/**
 * AssetFilterInterface all filters declared in your config.ini must implement 
 * this interface or exceptions will be thrown.
 *
 * @package asset_compress
 */
interface AssetFilterInterface {
/**
 * Filter content and return the filtered results.
 *
 * @param string $content 
 * @return string Filtered content
 */
	public function input($filename, $content);

	public function output($targetFile, $content);

	public function settings($settings);
}

class AssetFilter implements AssetFilterInterface {

	protected $_settings = array();

	public function settings($settings) {
		$this->_settings = $settings;
	}

	public function input($filename, $content) {
		return $content;
	}

	public function output($target, $content) {
		return $content;
	}
}
