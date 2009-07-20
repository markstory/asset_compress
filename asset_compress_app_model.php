<?php

class AssetCompressAppModel extends AppModel {

/**
 * find the asset_compress path
 *
 * @return void
 **/
	protected function _pluginPath() {
		$paths = Configure::read('pluginPaths');
		foreach ($paths as $path) {
			if (is_dir($path . 'asset_compress')) {
				return $path . 'asset_compress' . DS;
			}
		}
		throw new Exception('Could not find my directory, bailing hard!');
	}
}

?>