<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * CssMin filter.
 *
 * Allows you to filter Css files through CssMin. You need to put CssMin in your application's
 * vendors directories. You can get it from http://code.google.com/p/cssmin/
 *
 * @package asset_compress
 */
class CssMinFilter extends AssetFilter {

/**
 * Where CssMin can be found.
 *
 * @var array
 */
	protected $_settings = array(
		'path'    => 'cssmin/CssMin.php',
		'baseUrl' => '/'
	);

	protected static $path = '';

	/**
	 * Apply CssMin to $content.
	 *
	 * @param string $filename target filename
	 * @param string $content Content to filter.
	 * @throws Exception
	 * @return string
	 */
	public function input($filename, $content) {
		App::import('Vendor', 'cssmin', array('file' => $this->_settings['path']));
		if (!class_exists('CssMin')) {
			throw new Exception(sprintf('Cannot not load filter class "%s".', 'CssMin'));
		}

		$file = new File($filename);
		/*
		 * Retrieve Folder filepath
		 */
		$path = $file->Folder->path;
		/*
		 * Retrieve relative path from wwwroot
		 */
		$path = str_replace(WWW_ROOT, '', $path);
		/*
		 * Add Baseurl to path
		 */
		$path = $this->_settings['baseUrl'] . str_replace(DS, '/', $path);
		/*
		 * Remove trailing slash
		 */
		$path = rtrim($path, '/');
		/*
		 * Store path for callback
		 */
		self::$path = $path;
		/*
		 * Replace Urls
		 */
		$content = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/', array('CssMinFilter', 'updateurl'), $content);
		/*
		 * return minified content
		 * and heade with info of filename
		 */
		$header  = "\n\n/* <[" . $path.'/'.$file->name . "]> */\n";
		$content = CssMin::minify($content);
		return $header . $content;
	}

	public function output($filename, $content) {
		/*
		 * Do nothing !!!
		 */
		return $content;
	}

	protected static function cleanPath ($path) {
		$pattern = '/\w+\/\.\.\//';
		while(preg_match($pattern,$path)){
			$path = preg_replace($pattern, '', $path);
		}
		return $path;
	}

	protected static function updateurl ($matches) {
		$isImport = ($matches[0][0] === '@');
        // determine URI and the quote character (if any)
        if ($isImport) {
            $quoteChar = $matches[1];
            $uri = $matches[2];
        } else {
            // $matches[1] is either quoted or not
            $quoteChar = ($matches[1][0] === "'" || $matches[1][0] === '"')
                ? $matches[1][0]
                : '';
            $uri = ($quoteChar === '')
                ? $matches[1]
                : substr($matches[1], 1, strlen($matches[1]) - 2);
        }

		// root-relative       protocol (non-data)             data protocol
		if ($uri[0] !== '/' && $uri[0] !== '#' && strpos($uri, '://') === false && strpos($uri, 'data:') !==  0){
        	$uri = self::cleanPath (self::$path.'/'.$uri);
		}

		//var_dump("url({$quoteChar}{$uri}{$quoteChar})");

        return $isImport
            ? "@import {$quoteChar}{$uri}{$quoteChar}"
            : "url({$quoteChar}{$uri}{$quoteChar})";
	}
}