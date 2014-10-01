<?php
namespace AssetCompress;

/**
 * AssetFilterInterface all filters declared in your config.ini must implement
 * this interface or exceptions will be thrown.
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
 * Gets settings for this filter. Will always include 'paths'
 * key which points at paths available for the type of asset being generated.
 *
 * @param array $settings Array of settings.
 */
	public function settings($settings);

}
