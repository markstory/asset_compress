<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetFilter;
use CssMin;

/**
 * CssMin filter.
 *
 * Allows you to filter Css files through CssMin. You need to install CssMin with composer.
 */
class CssMinFilter extends AssetFilter {

/**
 * Apply CssMin to $content.
 *
 * @param string $filename target filename
 * @param string $content Content to filter.
 * @throws Exception
 * @return string
 */
	public function output($filename, $content) {
		if (!class_exists('CssMin')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'CssMin'));
		}
		return CssMin::minify($content);
	}

}
