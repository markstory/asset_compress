<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * @see http://leafo.net/lessphp/
 */
class LessPhp extends AssetFilter {
  
	public function input($file, $content) {
	  print $file;
		//App::import('Vendor', 'lessphp', 'lessc.inc.php');
		require_once('C:\www\samsherlock.com\public_html\vendors\lessphp\lessc.inc.php');
		$lessc = new lessc();
		$content = $lessc->parse($content);
	  print $content;
        return $content;
	}

    public function output($target, $content) {
	  print $target;
        return $content;
    }
}