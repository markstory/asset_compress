<?php

App::import('Lib', 'AssetCompress.AssetConfig');
App::import('Lib', 'AssetCompress.AssetCompiler');
App::import('Lib', 'AssetCompress.AssetCache');

class AssetsController extends AssetCompressAppController {
	public $name = 'Assets';
	public $uses = array();
	public $layout = 'script';
	public $viewPath = 'generic';

	public function beforeFilter() {
	
	}

/**
 * Get a built file.  Use query parameters for dynamic builds.
 * for dynamic builds to work, you must be in debug mode, and not have the same
 * build file already defined.
 */
	public function get($build) {
		$Config = AssetConfig::buildFromIniFile();
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
		}

		$this->header('Content-Type: ' . $this->_getContentType($Config->getExt($build)));
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

}
