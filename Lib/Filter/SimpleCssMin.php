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
 *
 * Based on code by Manas Tungare (https://gist.github.com/manastungare/2625128).
 * Copyright (c) 2009 and onwards, Manas Tungare.
 * Creative Commons Attribution, Share-Alike.
 *
 * @param string $filename target filename
 * @param string $content Content to filter.
 * @return string
 */
	public function output($filename, $content) {
		// Remove comments
		$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);

		// Replace newlines with spaces
		// (replacing by empty string may break media queries conditions that are splitted over multiple lines)
		$content = preg_replace('/\n/m', ' ', $content);

		// Replace consecutive whitespaces by single one
		$content = preg_replace('/\s{2,}/', ' ', $content);

		// Remove spaces before and after any of { } , : >
		$content = preg_replace('/\s*({|}|,|\:|;|>)\s*/', '$1', $content);

		// Remove spaces left parenthesis or before right parenthesis
		$content = preg_replace('/(\()\s*|\s*(\))/', '$1$2', $content);

		// Replace ;} with }
		$content = preg_replace('/;}/', '}', $content);

		// Hex colors compression
		$content = preg_replace('/#(.)\1(.)\2(.)\3/', '#$1$2$3', $content);

		// Trim
		$content = preg_replace('/^\s*|\s*$/', '', $content);

		return $content;
	}

}
