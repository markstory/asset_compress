<?php

App::import('Lib', 'AssetCompress.AssetConfig');
App::import('Lib', 'AssetCompress.AssetCompiler');
App::import('Lib', 'AssetCompress.AssetCache');

class AssetsController extends AssetCompressAppController {
	public $name = 'Assets';
	public $uses = array();
	public $viewPath = 'generic';
	public $_Config;

	public function beforeFilter() {
		$this->configFile = CONFIGS . 'asset_compress.ini';
	}

/**
 * Get a built file.  Use query parameters for dynamic builds.
 * for dynamic builds to work, you must be in debug mode, and not have the same
 * build file already defined.
 */
	public function get($build) {
		$Config = $this->_getConfig();

		if (
			isset($this->params['url']['ext']) &&
			in_array($this->params['url']['ext'], $Config->extensions())
		) {
			$build .= '.' . $this->params['url']['ext'];
		}

		if (isset($this->params['url']['theme'])) {
			$Config->theme($this->params['url']['theme']);
		}

		// dynamic build file
		if (Configure::read('debug') > 0 && $Config->files($build) === array()) {
			$files = array();
			if (isset($this->params['url']['file'])) {
				$files = $this->params['url']['file'];
			}
			$Config->files($build, $files);
		}
		try {
			$Compiler = new AssetCompiler($Config);
			$contents = $Compiler->generate($build);

			if ($Config->cachingOn($build)) {
				$Cache = new AssetCache($Config);
				$Cache->write($build, $contents);
			}
		} catch (Exception $e) {
			$this->log($e->getMessage());
			$this->header('HTTP/1.1 404 Not Found');
			$this->autoRender = false;
			return;
		}

		$this->header('Content-Type: ' . $this->_getContentType($Config->getExt($build)));
		$this->set('contents', $contents);
		$this->layout = 'script';
		$this->render('contents');
	}

/**
 * Helper method for getting ext -> mime type mappings.
 */
	protected function _getContentType($ext) {
		switch ($ext) {
			case 'js':
				return 'text/javascript';
			case 'css':
				return 'text/css';
		}
	}

/**
 * Config setter, used for testing the controller.
 */
	protected function _getConfig() {
		if (empty($this->_Config)) {
			$this->_Config = AssetConfig::buildFromIniFile();
		}
		return $this->_Config;
	}

}
