<?php
App::uses('AssetScanner', 'AssetCompress.Lib');

/**
 * Writes compiled assets to the filesystem
 * with optional timestamps.
 *
 */
class AssetCache {

	protected $_Config = null;
	protected $_invalidated = null;

	public function __construct(AssetConfig $config) {
		$this->_Config = $config;
	}

/**
 * Writes content into a file
 *
 * @param string $build The filename to write.
 * @param string $content The contents to write.
 * @throws RuntimeException
 */
	public function write($build, $content) {
		$ext = $this->_Config->getExt($build);
		$path = $this->_Config->cachePath($ext);

		if (!is_writable($path)) {
			throw new RuntimeException('Cannot write cache file. Unable to write to ' . $path);
		}
		$filename = $this->buildFileName($build);
		$success = file_put_contents($path . $filename, $content) !== false;
		$this->finalize($build);
		return $success;
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
		$configTime = $this->_Config->modifiedTime();
		$buildTime = filemtime($buildFile);

		if ($configTime >= $buildTime) {
			return false;
		}

		$Scanner = new AssetScanner($this->_Config->paths($ext, $target), $theme);

		foreach ($files as $file) {
			$path = $Scanner->find($file);
			if ($Scanner->isRemote($path)) {
				$time = $this->getRemoteFileLastModified($path);
			} else {
				$time = filemtime($path);
			}
			if ($time === false || $time >= $buildTime) {
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

		// @codingStandardsIgnoreStart
		$fp = @fopen($url, 'rb');
		// @codingStandardsIgnoreEnd
		if (!$fp) {
			return false;
		}

		$metadata = stream_get_meta_data($fp);
		foreach ($metadata['wrapper_data'] as $response) {
			// case: redirection
			if (substr(strtolower($response), 0, 10) === 'location: ') {
				$newUri = substr($response, 10);
				fclose($fp);
				return $this->getRemoteFileLastModified($newUri);
			}
			// case: last-modified
			// @codingStandardsIgnoreStart
			elseif (substr(strtolower($response), 0, 15) === 'last-modified: ') {
			// @codingStandardsIgnoreEnd
				$unixtime = strtotime(substr($response, 15));
				break;
			}
		}

		fclose($fp);
		return $unixtime;
	}

/**
 * Invalidate a build before re-generating the file.
 *
 * @param string $build The build to invalidate.
 * @return void
 */
	public function invalidate($build) {
		$ext = $this->_Config->getExt($build);
		if (!$this->_Config->get($ext . '.timestamp')) {
			return false;
		}
		$this->_invalidated = $build;
		$this->setTimestamp($build, 0);
	}

/**
 * Finalize a build after written to filesystem.
 *
 * @param string $build The build to finalize.
 * @return void
 */
	public function finalize($build) {
		$ext = $this->_Config->getExt($build);
		if (!$this->_Config->get($ext . '.timestamp')) {
			return;
		}
		$data = $this->_readTimestamp();
		$name = $this->buildCacheName($build);
		if (!isset($data[$name])) {
			return;
		}
		$time = $data[$name];
		unset($data[$name]);
		$this->_invalidated = null;
		$name = $this->buildCacheName($build);
		$data[$name] = $time;
		$this->_writeTimestamp($data);
	}

/**
 * Set the timestamp for a build file.
 *
 * @param string $build The name of the build to set a timestamp for.
 * @param integer $time The timestamp.
 * @return void
 */
	public function setTimestamp($build, $time) {
		$ext = $this->_Config->getExt($build);
		if (!$this->_Config->get($ext . '.timestamp')) {
			return;
		}
		$data = $this->_readTimestamp();
		$build = $this->buildCacheName($build);
		$data[$build] = $time;
		$this->_writeTimestamp($data);
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
		$name = $this->buildCacheName($build);
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
 * Write timestamps to either the fast cache, or the serialized file.
 *
 * @param array $data An array of timestamps for build files.
 * @return void
 */
	protected function _writeTimestamp($data) {
		if ($this->_Config->general('cacheConfig')) {
			Cache::write(AssetConfig::CACHE_BUILD_TIME_KEY, $data, AssetConfig::CACHE_CONFIG);
		}
		$data = serialize($data);
		file_put_contents(TMP . AssetConfig::BUILD_TIME_FILE, $data);
		chmod(TMP . AssetConfig::BUILD_TIME_FILE, 0644);
	}

/**
 * Get the final filename for a build. Resolves
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
 * Get the cache name a build. 
 *
 * @param string $build The build target name.
 * @return string The build cache name.
 */
	public function buildCacheName($build) {
		$name = $this->buildFileName($build, false);
		if ($build == $this->_invalidated) {
			return '~' . $name;
		}
		return $name;
	}
	
/**
 * Modify a file name and append in the timestamp
 *
 * @param string $file The filename.
 * @param integer $time The timestamp.
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
