<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * SimpleCssMin filter.
 * Easily compresses CSS files. Allows to avoid problems that some minifiers have
 * with previously processed files.
 */
class SimpleCssMin extends AssetFilter {

/**
 * Apply SimpleCssMin to $content.
 * Code credits to Manas Tungare, available in https://gist.github.com/manastungare/2625128.
 *
 * @param string $filename target filename
 * @param string $content Content to filter.
 * @return string
 */
	public function output($filename, $content) {
		// Remove comments
		$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
		 
		// Remove space after colons
		$content = str_replace(': ', ':', $content);
		 
		// Remove whitespace
		$content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $content);
         
		return $content;
	}

}
