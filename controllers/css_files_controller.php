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
 * If debug < 2 concatenations will be cached to disk.  You can use the cacheFiles config setting
 * to write concatenated/filtered files to a webroot path.
 *
 * Files to be appended are added as query string parameters `file[]=name`
 *
 * @return void
 **/
	public function get($keyname = null) {
		$objects = array();
		if (!empty($this->params['url']['file'])) {
			$objects = $this->params['url']['file'];
		}

		$compress = Cache::read('css-' . $keyname, 'asset_compress');
		if (empty($compress)) {
			try {
				$compress = $this->CssFile->process($objects);
				if (Configure::read('debug') < 2 && $this->CssFile->cachingOn()) {
					$this->CssFile->cache($keyname, $compress);
				}
			} catch (Exception $e) {
				$this->log($e->getMessage());
			}
		}
		$this->header('Content-Type: text/css');
		$this->layout = 'script';
		$this->viewPath = 'generic';
		$this->set('contents', $compress);
		$this->render('contents');
	}
}