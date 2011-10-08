<?php

App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('AssetCache', 'AssetCompress.Lib');
App::uses('AssetCompiler', 'AssetCompress.Lib');

class AssetsController extends AssetCompressAppController {
	public $name = 'Assets';
	public $uses = array();
	public $layout = 'script';
	public $viewPath = 'generic';
	public $_Config;

	public function beforeFilter() {
		$this->configFile = APP . 'Config' . DS . 'asset_compress.ini';
	}

/**
 * Get a built file.  Use query parameters for dynamic builds.
 * for dynamic builds to work, you must be in debug mode, and not have the same
 * build file already defined.
 */
	public function get($build) {
		$Config = $this->_getConfig();

		if (
			isset($this->request->params['url']['ext']) &&
			in_array($this->request->params['url']['ext'], $Config->extensions())
		) {
			$build .= '.' . $this->request->params['url']['ext'];
		}

		// dynamic build file
		if (Configure::read('debug') > 0 && $Config->files($build) === array()) {
			$files = array();
			if (isset($this->request->params['url']['file'])) {
				$files = $this->request->params['url']['file'];
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
			throw new NotFoundException();
		}

		$this->response->header('Content-Type: ' . $this->_getContentType($Config->getExt($build)));
		$this->set('contents', $contents);
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
