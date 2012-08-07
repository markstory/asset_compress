<?php
App::uses('AssetScanner', 'AssetCompress.Lib');

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
 * @throws RuntimeException
 */
	public function write($filename, $content) {
		$ext = $this->_Config->getExt($filename);
		$path = $this->_Config->cachePath($ext);

		if (!is_writable($path)) {
			throw new RuntimeException('Cannot write cache file. Unable to write to ' . $path);
		}
		$filename = $this->buildFileName($filename);
		return file_put_contents($path . $filename, $content) !== false;
	}

/**
 * Check to see if a cached build file is 'fresh'.
 * Fresh cached files have timestamps newer than all of the component
 * files.
 *
 * @param string $target The target file being built.
 * @return boolean
 */
	public function isFresh($target) {
		$ext = $this->_Config->getExt($target);
		$files = $this->_Config->files($target);

		$theme = $this->_Config->theme();
		$target = $this->buildFileName($target);

		$buildFile = $this->_Config->cachePath($ext) . $target;

		if (!file_exists($buildFile)) {
			return false;
		}
		$buildTime = filemtime($buildFile);
		$Scanner = new AssetScanner($this->_Config->paths($ext), $theme);

		foreach ($files as $file) {
			$path = $Scanner->find($file);
			if ($Scanner->isRemote($path)) {
				$time = $this->getRemoteFileLastModified($path);
			} else {
				$time = filemtime($path);
			}
			if ($time === false  ||  $time >= $buildTime) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Gets the modification time of a remote $url.
	 * Based on: http://www.php.net/manual/en/function.filemtime.php#81194
	 * @param type $url
	 * @return The last modified time of the $url file, in Unix timestamp, or false it can't be read.
	 */
	public function getRemoteFileLastModified($url) {
		// default
		$unixtime = 0;

		$fp = @fopen($url, 'rb');
		if (!$fp) {
			return false;
		}

		$metadata = stream_get_meta_data($fp);
		foreach ($metadata['wrapper_data'] as $response) {
			// case: redirection
			if (substr(strtolower($response), 0, 10) == 'location: ') {
				$newUri = substr($response, 10);
				fclose($fp);
				return $this->getRemoteFileLastModified($newUri);
			}
			// case: last-modified
			elseif (substr(strtolower($response), 0, 15) == 'last-modified: ') {
				$unixtime = strtotime(substr($response, 15));
				break;
			}
		}
		
		fclose($fp);
		return $unixtime;
	}
	
/**
 * Set the timestamp for a build file.
 *
 * @param string $build The name of the build to set a timestamp for.
 * @param int $time The timestamp.
 */
	public function setTimestamp($build, $time) {
		$ext = $this->_Config->getExt($build);
		if (!$this->_Config->get($ext . '.timestamp')) {
			return false;
		}
		$data = $this->_readTimestamp();
		$build = $this->buildFileName($build, false);
		$data[$build] = $time;
		if ($this->_Config->general('cacheConfig')) {
			Cache::write(AssetConfig::CACHE_BUILD_TIME_KEY, $data, AssetConfig::CACHE_CONFIG);
		}
		$data = serialize($data);
		file_put_contents(TMP . AssetConfig::BUILD_TIME_FILE, $data);
		chmod(TMP . AssetConfig::BUILD_TIME_FILE, 0777);
	}

/**
 * Get the last build timestamp for a given build.
 *
 * Will either read the cached version, or the on disk version. If
 * no timestamp is found for a file, a new time will be generated and saved.
 *
 * If timestamps are disabled, false will be returned.
 *
 * @param string $build The build to get a timestamp for.
 * @return mixed The last build time, or false.
 */
	public function getTimestamp($build) {
		$ext = $this->_Config->getExt($build);
		if (!$this->_Config->get($ext . '.timestamp')) {
			return false;
		}
		$data = $this->_readTimestamp();
		$name = $this->buildFileName($build, false);
		if (!empty($data[$name])) {
			return $data[$name];
		}
		$time = time();
		$this->setTimestamp($build, $time);
		return $time;
	}

/**
 * Read timestamps from either the fast cache, or the serialized file.
 *
 * @return array An array of timestamps for build files.
 */
	protected function _readTimestamp() {
		$data = array();
		$cachedConfig = $this->_Config->general('cacheConfig');
		if ($cachedConfig) {
			$data = Cache::read(AssetConfig::CACHE_BUILD_TIME_KEY, AssetConfig::CACHE_CONFIG);
		}
		if (empty($data) && file_exists(TMP . AssetConfig::BUILD_TIME_FILE)) {
			$data = file_get_contents(TMP . AssetConfig::BUILD_TIME_FILE);
			if ($data) {
				$data = unserialize($data);
			}
		}
		return $data;
	}

/**
 * Get the final filename for a build.  Resolves
 * theme prefixes and timestamps.
 *
 * @param string $target The build target name.
 * @return string The build filename to cache on disk.
 */
	public function buildFileName($target, $timestamp = true) {
		$file = $target;
		if ($this->_Config->isThemed($target)) {
			$file = $this->_Config->theme() . '-' . $target;
		}
		if ($timestamp) {
			$time = $this->getTimestamp($target);
			$file = $this->_timestampFile($file, $time);
		}
		return $file;
	}

/**
 * Modify a file name and append in the timestamp
 *
 * @param string $file The filename.
 * @param int $time The timestamp.
 * @return string The build filename to cache on disk.
 */
	protected function _timestampFile($file, $time) {
		if (!$time) {
			return $file;
		}
		$pos = strrpos($file, '.');
		$name = substr($file, 0, $pos);
		$ext = substr($file, $pos);
		return $name . '.v' . $time . $ext;
	}
}
