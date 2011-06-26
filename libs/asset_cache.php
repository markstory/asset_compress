<?php
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
		if ($this->_Config->get($ext . '.timestamp') == true) {
			$filename = $this->_timestampFilename($filename);
		}
		return file_put_contents($path . $filename, $content);
	}

	protected function _timestampFilename($file) {
		$pos = strrpos($file, '.');
		$name = substr($file, 0, $pos);
		$ext = substr($file, $pos);
		
		$tsFileVal = $this->_Config->getUseTsFileValue();
		if(!empty($tsFileVal)) return $name . '.v' . $tsFileVal . $ext;
		
		return $name . '.v' . time() . $ext;
	}
}
