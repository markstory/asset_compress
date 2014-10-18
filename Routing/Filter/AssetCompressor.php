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
		$config = $this->_getConfig();
		$production = !Configure::read('debug');
		if ($production && !$config->general('alwaysEnableController')) {
			return;
		}

		$build = $this->_getBuild($url);
		if ($build === false) {
			return;
		}

		if (isset($event->data['request']->query['theme'])) {
			$config->theme($event->data['request']->query['theme']);
		}

		// Dynamically defined build file. Disabled in production for
		// hopefully obvious reasons.
		if ($config->files($build) === array()) {
			$files = array();
			if (isset($event->data['request']->query['file'])) {
				$files = $event->data['request']->query['file'];
			}
			$config->files($build, $files);
		}

		// Use the TMP dir for dev builds.
		// This is to avoid permissions issues with the configured paths.
		$ext = $config->getExt($build);
		$config->cachePath($ext, TMP);

		try {
			$compiler = new AssetCompiler($config);
			$cache = new AssetCache($config);
			if ($cache->isFresh($build)) {
				$contents = file_get_contents(TMP . $build);
			} else {
				$contents = $compiler->generate($build);
			}
		} catch (Exception $e) {
			throw new NotFoundException($e->getMessage());
		}

		$event->data['response']->type($config->getExt($build));
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
