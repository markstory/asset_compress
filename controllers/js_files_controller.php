<?php
/**
 * JsFiles Controller handles Javascript file requests.
 *
 * @package asset_compress
 * @author Mark Story
 **/
class JsFilesController extends AssetCompressAppController {

	public $name = 'JsFiles';
/**
 * beforefilter callback
 *
 * @return void
 **/
	public function beforeFilter() {
		if (isset($this->Auth)) {
			$this->Auth->enabled = false;
		}
	}
/**
 * Concatenates the requested Objects/files Together based on the settings in the config.ini
 *
 * @return void
 **/
	public function join() {
		$objects = func_get_args();
		$key = implode('-', $objects);
		$compress = Cache::read($key, 'asset_compress');
		if (empty($compress)) {
			$compress = $this->JsFile->process($objects);
			if (Configure::read('debug') == 0) {
				Cache::write($key, $compress, 'asset_compress');
			}
		}
		$this->header('Content-Type', 'text/javascript');
		$this->layout = 'script';
		$this->set('contents', $compress);
		$this->render('contents');
	}
}