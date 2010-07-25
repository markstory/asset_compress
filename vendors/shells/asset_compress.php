<?php
/**
 * Asset Compress Shell
 *
 * Assists in clearing and creating the build files this plugin makes.
 *
 * @package AssetCompress
 * @author Mark Story
 */
class AssetCompressShell extends Shell {
/**
 * models used
 *
 * @var array
 */
	public $uses = array('AssetCompress.JsFile', 'AssetCompress.CssFile');

/**
 * Clears the build directories for both CSS and JS
 *
 * @return void
 */
	public function clear() {
		$this->out('Clearing Javascript build files:');
		$this->hr();
		$this->_clearJs();

		$this->out('');
		$this->out('Clearing CSS build files:');
		$this->hr();
		$this->_clearCss();
		
		$this->out('');
		$this->out('Updating build timestamp file:');
		$this->hr();
		$this->JsFile->clearBuildTimestamp();
		$this->JsFile->createBuildTimestamp();
		$this->out('Complete');
	}

/**
 * clear the js files.
 *
 * @return void
 */
	protected function _clearJs() {
		if (!$this->JsFile->cachingOn()) {
			$this->out('Caching not enabled for Javascript files, skipping.');
		}
		$path = $this->JsFile->cacheDir();
		$this->_clearDirectory($path);
	}

/**
 * clear the css files.
 *
 * @return void
 */
	protected function _clearCss() {
		if (!$this->CssFile->cachingOn()) {
			$this->out('Caching not enabled for CSS files, skipping.');
		}
		$path = $this->CssFile->cacheDir();
		$this->_clearDirectory($path);
	}

/**
 * Clears a directory of cached files with the correct header.
 *
 * @return void
 */
	protected function _clearDirectory($path) {
		$dir = new DirectoryIterator($path);
		foreach ($dir as $file) {
			if(in_array($file->getFilename(), array('.', '..'))) {
				continue;
			}
			$fileInfo = new SplFileObject($file->getPathname());
			$line = $fileInfo->fgets();
			if (preg_match('#^/\* asset_compress \d+ \*/$#', $line)) {
				$pathName = $fileInfo->getPathname();
				unset($fileInfo);
				$this->out('Deleting ' . $pathName);
				unlink($pathName);
			}
		}
	}

/**
 * help
 *
 * @return void
 */
	public function help() {
		$this->out('Asset Compress Shell');
		$this->hr();
		$this->out('Usage: cake asset_compress <command>');
		$this->hr();
		$this->out("clear - Clears all existing build files.");
		$this->out();
	}
}