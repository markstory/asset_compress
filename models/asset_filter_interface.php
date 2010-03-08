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
	public function filter($content);
}