<?php
/**
 * CssFiles Controller handles CSS file requests.
 *
 * @package asset_compress
 * @author Mark Story
 **/
class CssFilesController extends AssetCompressAppController {

	public $name = 'CssFiles';
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
			$compress = $this->CssFile->process($objects);
			if (Configure::read('debug') == 0) {
				Cache::write($key, $compress, 'asset_compress');
			}
		}
		$this->header('Content-Type', 'text/css');
		$this->layout = 'script';
		$this->viewPath = 'generic';
		$this->set('contents', $compress);
		$this->render('contents');
	}
}