<?php

App::uses('DispatcherFilter', 'Routing');
App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('AssetCompiler', 'AssetCompress.Lib');
App::uses('AssetCache', 'AssetCompress.Lib');

class AssetCompressor extends DispatcherFilter {

/**
 * Filter priority, we need it to run before router
 *
 * @var integer
 */
	public $priority = 9;

/**
 * Object containing configuration settings for asset compressor
 *
 * @var AssetConfig
 */
	protected $_Config;

/**
 * Checks if request is for a compiled asset, otherwise skip any operation
 *
 * @param CakeEvent $event containing the request and response object
 * @throws NotFoundException
 * @return CakeResponse if the client is requesting a recognized asset, null otherwise
 */
	public function beforeDispatch(CakeEvent $event) {
		$url = $event->data['request']->url;
		$Config = $this->_getConfig();
		$production = !Configure::read('debug');
		if ($production && !$Config->general('alwaysEnableController')) {
			return;
		}

		$build = $this->_getBuild($url);
		if ($build === false) {
			return;
		}

		if (isset($event->data['request']->query['theme'])) {
			$Config->theme($event->data['request']->query['theme']);
		}

		// Dynamically defined build file. Disabled in production for
		// hopefully obvious reasons.
		if ($Config->files($build) === array()) {
			$files = array();
			if (isset($event->data['request']->query['file'])) {
				$files = $event->data['request']->query['file'];
			}
			$Config->files($build, $files);
		}

		try {
			$Compiler = new AssetCompiler($Config);
			$mtime = $Compiler->getLastModified($build);
			$event->data['response']->modified($mtime);
			if ($event->data['response']->checkNotModified($event->data['request'])) {
				$event->stopPropagation();
				return $event->data['response'];
			}
			$contents = $Compiler->generate($build);
		} catch (Exception $e) {
			throw new NotFoundException($e->getMessage());
		}

		$event->data['response']->type($Config->getExt($build));
		$event->data['response']->body($contents);
		$event->stopPropagation();
		return $event->data['response'];
	}

/**
 * Returns the build name for a requested asset
 *
 * @return boolean|string false if no build can be parsed from URL
 * with url path otherwise
 */
	protected function _getBuild($url) {
		$parts = explode('.', $url);
		if (count($parts) < 2) {
			return false;
		}

		$path = $this->_getConfig()->cachePath($parts[(count($parts) - 1)]);
		if (empty($path)) {
			return false;
		}

		$path = str_replace(WWW_ROOT, '', $path);
		if (strpos($url, $path) !== 0) {
			return false;
		}
		return str_replace($path, '', $url);
	}

/**
 * Config setter, used for testing the filter.
 */
	protected function _getConfig() {
		if (empty($this->_Config)) {
			$this->_Config = AssetConfig::buildFromIniFile();
		}
		return $this->_Config;
	}

}
