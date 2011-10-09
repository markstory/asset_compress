<?php
App::import('Lib', 'AssetCompress.AssetScanner');

/**
 * Writes compiled assets to the filesystem
 * with optional timestamps.
 *
 * @package asset_compress
 */
class AssetCache {

	protected $_Config = null;

	public function __construct(AssetConfig $config) {
		$this->_Config = $config;
	}

/**
 * Writes content into a file
 *
 * @param string $filename The filename to write.
 * @param string $contents The contents to write.
 */
	public function write($filename, $content) {
		$ext = $this->_Config->getExt($filename);
		$path = $this->_Config->cachePath($ext);

		if (!is_writable($path)) {
			throw new RuntimeException('Cannot write cache file. Unable to write to ' . $path); 
		}

		if ($this->_Config->isThemed($filename)) {
			$filename = $this->_Config->theme() . '-' . $filename;
		}
		if ($this->_Config->get($ext . '.timestamp') == true) {
			$filename = $this->_timestampFilename($filename);
		}
		return file_put_contents($path . $filename, $content);
	}

/**
 * Check to see if a cached build file is 'fresh'.
 * Fresh cached files have timestamps newer than all of the component
 * files.
 *
 * @param string $target The target file being built.
 * @return boolean
 */
	public function isFresh($target)
	{
		$ext = $this->_Config->getExt($target);
		$files = $this->_Config->files($target);
	
		$theme = null;
		if ($this->_Config->isThemed($target)) {
			$theme = $this->_Config->theme();
			$target = $this->_Config->theme() . '-' . $target;
		}

		$buildFile = $this->_Config->cachePath($ext) . $target;

		if ($this->_Config->get($ext . '.timestamp') == true) {
			$buildFile = $this->_timestampFilename($buildFile);
		}

		if (!file_exists($buildFile)) {
			return false;
		}
		$buildTime = filemtime($buildFile);
		$Scanner = new AssetScanner($this->_Config->paths($ext), $theme);

		foreach ($files as $file) {
			$path = $Scanner->find($file);
			$time = filemtime($path);
			if ($time >= $buildTime) {
				return false;
			}
		}
		return true;
	}

	protected function _timestampFilename($file) {
		$pos = strrpos($file, '.');
		$name = substr($file, 0, $pos);
		$ext = substr($file, $pos);
		$time = time();

		if ($this->_Config->general('timestampFile')) {
			$time = $this->_Config->readTimestampFile();
		}
		return $name . '.v' . $time . $ext;
	}
}
