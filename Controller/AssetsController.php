<?php
App::uses('AssetCompressAppController', 'AssetCompress.Controller');
App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('AssetCompiler', 'AssetCompress.Lib');
App::uses('AssetCache', 'AssetCompress.Lib');

class AssetsController extends AssetCompressAppController {

	public $uses = array();

	public $viewPath = 'Generic';

	public $configFile;

	protected $_Config;

	public function beforeFilter() {
		$this->configFile = APP . 'Config' . DS . 'asset_compress.ini';
	}

/**
 * Get a built file.  Use query parameters for dynamic builds.
 * for dynamic builds to work, you must be in debug mode, and not have the same
 * build file already defined.
 *
 * @throws ForbiddenException
 * @throws NotFoundException
 */
	public function get($build) {
		$Config = $this->_getConfig();
		$production = Configure::read('debug') == 0;

		if ($production && !$Config->general('alwaysEnableController')) {
			throw new ForbiddenException();
		}

		if (
			isset($this->request->params['ext']) &&
			in_array($this->request->params['ext'], $Config->extensions())
		) {
			$build .= '.' . $this->request->params['ext'];
		}

		if (isset($this->request->query['theme'])) {
			$Config->theme($this->request->query['theme']);
		}

		// Dynamically defined build file. Disabled in production for
		// hopefully obvious reasons.
		if ($Config->files($build) === array()) {
			$files = array();
			if (isset($this->request->query['file'])) {
				$files = $this->request->query['file'];
			}
			$Config->files($build, $files);
		}
		try {
			$Compiler = new AssetCompiler($Config);
			$contents = $Compiler->generate($build);
		} catch (Exception $e) {
			$message = (Configure::read('debug') > 0) ? $e->getMessage() : '';
			throw new NotFoundException($message);
		}

		$this->response->type($Config->getExt($build));
		$this->set('contents', $contents);
		$this->layout = 'script';
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
