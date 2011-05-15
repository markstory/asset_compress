<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');

/**
 * A preprocessor that inlines files referenced by 
 * @import() statements in css files.
 *
 * @package asset_compress
 */
class ImportInline extends AssetFilter {

	public function input($filename, $content) {
	
	}
}
