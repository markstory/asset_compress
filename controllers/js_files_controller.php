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
		if ($this->view === 'Theme' && !empty($this->theme)) {
			$this->JsFile->addTheme($this->theme);
		}
	}
/**
 * beforeRender callback
 *
 * @return void
 **/
	public function beforeRender() {
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
		if (! $this->JsFile->validExtension($this->params['pass'][0]) && strtolower($this->params['url']['ext'])!='js') {
			$this->cakeError('error404');
			return;
		}
		$objects = array();
		if (!empty($this->params['url']['file'])) {
			$objects = $this->params['url']['file'];
		}
		$compress = '';
		try {
			$compress = $this->JsFile->process($objects);
			if (Configure::read('debug') < 2 && $this->JsFile->cachingOn()) {
				$this->JsFile->cache($keyname, $compress);
			}
		} catch (Exception $e) {
			$this->log($e->getMessage());
		}
		$this->header('Content-Type: text/javascript');
		$this->layout = 'script';
		$this->viewPath = 'generic';
		$this->set('contents', $compress);
		$this->render('contents');
	}
}