<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');

/**
 * JS Strip Comments filter.
 *
 * Strips comments from JS input.
 *
 * @package asset_compress
 * @author Mark Story
 */
class JsStripCommentsFilter implements AssetFilterInterface {

	public function filter($content) {
		$patterns = array(
			'#^\h*//.*\v*#m',
			'#^\h*/\*(?!!)(?:.(?!/)|[^\*](?=/)|(?<!\*)/)*\*/\v*#sm',
		);
		return preg_replace($patterns, '', $content);
	}
}
