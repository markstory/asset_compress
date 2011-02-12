<?php
/**
 * AssetProcessorInterface
 *
 * All processors declared in your config.ini must implement
 * this interface or exceptions will be thrown.
 *
 * @package asset_compress
 */
interface AssetProcessorInterface {
/**
 * Process content and return the processed results.
 *
 * @param string $fileName The filename of the file.
 * @param string $content The content of a file.
 * @return string Filtered content
 */
	public function process($fileName, $content);
}