<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');

/**
 * Css Strip Comments filter.
 *
 * Strips comments from CSS input.
 *
 * @package asset_compress
 * @author Mark Story
 */
class CssStripCommentsFilter implements AssetFilterInterface {
	public function filter($content) {
		return preg_replace('#^\h*/\*(?!!)(?:.(?!/)|[^\*](?=/)|(?<!\*)/)*\*/\v*#sm', '', $content);
	}
}
