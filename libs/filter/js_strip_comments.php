<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');

/**
 * JS Strip Comments filter.
 *
 * Stripts comments from JS input.
 *
 * @package asset_compress
 * @author Mark Story
 */
class JsStripCommentsFilter implements AssetFilterInterface {

	public function filter($content) {
		$patterns = array(
			'#^\h*//.*\n#m',
			'#^\h*/\*(?!!)(?:.(?!/)|[^\*](?=/)|(?<!\*)/)*\*/\n#sm',
		);
		return preg_replace($patterns, '', $content);
	}
}
?>