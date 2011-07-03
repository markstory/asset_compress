<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * @see http://leafo.net/lessphp/
 */
class LessPhp extends AssetFilter {

    public function output($file, $contents) {
		App::import('Vendor', 'lessphp', 'lessphp/lessc.inc.php');
		print "process less";
		$lessc = new lessc();
        return $lessc->parse($contents);
    }
}