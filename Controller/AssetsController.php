<?php
App::uses('AssetCompressAppController', 'AssetCompress.Controller');
App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('AssetCompiler', 'AssetCompress.Lib');
App::uses('AssetCache', 'AssetCompress.Lib');

class AssetsController extends AssetCompressAppController {
	public $name = 'Assets';
	public $uses = array();
	public $layout = 'script';
	public $viewPath = 'Generic';
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
			isset($this->request->params['ext']) &&
			in_array($this->request->params['ext'], $Config->extensions())
		) {
			$build .= '.' . $this->request->params['ext'];
		}

		if (isset($this->params['url']['theme'])) {
			$Config->theme($this->params['url']['theme']);
		}

		// dynamic build file
		if (Configure::read('debug') > 0 && $Config->files($build) === array()) {
			$files = array();
			if (isset($this->request->query['file'])) {
				$files = $this->request->query['file'];
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
			$this->response->statusCode(404);
			$this->autoRender = false;
			return;
		}

		$this->response->type($Config->getExt($build));
		$this->set('contents', $contents);
		$this->render('contents');
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
