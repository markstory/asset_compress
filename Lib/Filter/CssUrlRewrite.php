<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * A preprocessor that rewrite urls inside css to reference sprites and import
 *
 * @package asset_compress
 */
class CssUrlRewrite extends AssetFilter {
	
	/**
	 * @var array
	 */
	protected $_settings = array(
		'rewriteUrl' => '/'
	);
	protected $_pattern = '/url\\(\\s*([^\\)\\s]+)\\s*\\)/';
	protected $_path = '';
	
	/**
	 * Parse Css for url() and @import statement and rewrite with the new rewriteurl.
	 *
	 * @param string $filename target filename
	 * @param string $content Content to filter.
	 * @throws Exception
	 * @return string
	 */
	public function input($filename, $content) {
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
		$path = $this->_settings['rewriteUrl'] . str_replace(DS, '/', $path);
		/*
		 * Remove trailing slash
		*/
		$path = rtrim($path, '/');
		/*
		 * Store path for callback
		*/
		$this->_path = $path;
		/*
		 * Replace Urls
		*/
		$content = preg_replace_callback($this->_pattern, array($this, '_rewriteUrls'), $content);
		return $content;
	}
	
	protected function _rewriteUrls ($matches) {
		$isImport = ($matches[0][0] === '@');
		// determine URI and the quote character (if any)
		if ($isImport) {
			$quoteChar = $matches[1];
			$uri = $matches[2];
		} else {
			// $matches[1] is either quoted or not
			$quoteChar = ($matches[1][0] === "'" || $matches[1][0] === '"')	? $matches[1][0] : '';
			$uri       = ($quoteChar === '') ? $matches[1] : substr($matches[1], 1, strlen($matches[1]) - 2);
		}
		// root-relative       protocol (non-data)             data protocol
		if ($uri[0] !== '/' && $uri[0] !== '#' && strpos($uri, '://') === false && strpos($uri, 'data:') !==  0){
			$uri = $this->_cleanPath ($this->_path.'/'.$uri);
		}
	
		//var_dump("url({$quoteChar}{$uri}{$quoteChar})");
		return $isImport ? "@import {$quoteChar}{$uri}{$quoteChar}"	: "url({$quoteChar}{$uri}{$quoteChar})";
	}	

	protected function _cleanPath($path) {
		$pattern = '/\w+\/\.\.\//';
		while(preg_match($pattern,$path)){
			$path = preg_replace($pattern, '', $path);
		}
		return $path;
	}
	
}