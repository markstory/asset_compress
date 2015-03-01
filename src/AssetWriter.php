<?php
namespace AssetCompress;

use AssetCompress\AssetTarget;
use RuntimeException;

/**
 * Writes compiled assets to the filesystem
 * with optional timestamps.
 */
class AssetWriter
{
    const BUILD_TIME_FILE = 'asset_compress_build_time';

    protected $timestamp = [];

    protected $theme;

    protected $path;

    /**
     * An array of invalidated output files.
     *
     * @var array
     */
    protected $_invalidated = null;

    /**
     * Constructor.
     *
     * @param array $timestamp The map of extensions and timestamps
     * @param string $timestampPath The path to the timestamp file for assets.
     * @param string $theme The the theme being assets are being built for.
     */
    public function __construct(array $timestamp, $timestampPath, $theme = null)
    {
        $this->timestamp = $timestamp;
        $this->path = $timestampPath;
        $this->theme = $theme;
    }

    /**
     * Get the config options this object is using.
     *
     * @return array
     */
    public function config()
    {
        return [
            'theme' => $this->theme,
            'timestamp' => $this->timestamp,
            'path' => $this->path
        ];
    }

    /**
     * Writes content into a file
     *
     * @param AssetTarget $build The filename to write.
     * @param string $content The contents to write.
     * @throws RuntimeException
     */
    public function write(AssetTarget $build, $content)
    {
        $ext = $build->ext();
        $path = $build->outputDir();

        if (!is_writable($path)) {
            throw new RuntimeException('Cannot write cache file. Unable to write to ' . $path);
        }
        $filename = $this->buildFileName($build);
        $success = file_put_contents($path . DS . $filename, $content) !== false;
        $this->finalize($build);
        return $success;
    }

    /**
     * Check to see if a cached build file is 'fresh'.
     * Fresh cached files have timestamps newer than all of the component
     * files.
     *
     * @param AssetTarget $target The target file being built.
     * @return boolean
     */
    public function isFresh(AssetTarget $target)
    {
        $ext = $target->ext();
        $buildName = $this->buildFileName($target);
        $buildFile = $target->outputDir() . DS . $buildName;

        if (!file_exists($buildFile)) {
            return false;
        }
        $buildTime = filemtime($buildFile);

        foreach ($target->files() as $file) {
            $time = $file->modifiedTime();
            if ($time === false || $time >= $buildTime) {
                return false;
            }
        }
        return true;
    }

    /**
     * Invalidate a build before re-generating the file.
     *
     * @param string $build The build to invalidate.
     * @return void
     */
    public function invalidate(AssetTarget $build)
    {
        $ext = $build->ext();
        if (empty($this->timestamp[$ext])) {
            return false;
        }
        $this->_invalidated = $build->name();
        $this->setTimestamp($build, 0);
    }

    /**
     * Finalize a build after written to filesystem.
     *
     * @param AssetTarget $build The build to finalize.
     * @return void
     */
    public function finalize(AssetTarget $build)
    {
        $ext = $build->ext();
        if (empty($this->timestamp[$ext])) {
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
     * @param AssetTarget $build The name of the build to set a timestamp for.
     * @param int $time The timestamp.
     * @return void
     */
    public function setTimestamp(AssetTarget $build, $time)
    {
        $ext = $build->ext();
        if (empty($this->timestamp[$ext])) {
            return;
        }
        $data = $this->_readTimestamp();
        $name = $this->buildCacheName($build);
        $data[$name] = $time;
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
     * @param AssetTarget $build The build to get a timestamp for.
     * @return mixed The last build time, or false.
     */
    public function getTimestamp(AssetTarget $build)
    {
        $ext = $build->ext();
        if (empty($this->timestamp[$ext])) {
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
    protected function _readTimestamp()
    {
        $data = array();
        if (empty($data) && file_exists($this->path . static::BUILD_TIME_FILE)) {
            $data = file_get_contents($this->path . static::BUILD_TIME_FILE);
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
    protected function _writeTimestamp($data)
    {
        $data = serialize($data);
        file_put_contents($this->path . static::BUILD_TIME_FILE, $data);
        chmod($this->path . static::BUILD_TIME_FILE, 0644);
    }

    /**
     * Get the final filename for a build. Resolves
     * theme prefixes and timestamps.
     *
     * @param AssetTarget $target The build target name.
     * @return string The build filename to cache on disk.
     */
    public function buildFileName(AssetTarget $target, $timestamp = true)
    {
        $file = $target->name();
        if ($target->isThemed() && $this->theme) {
            $file = $this->theme . '-' . $file;
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
    public function buildCacheName($build)
    {
        $name = $this->buildFileName($build, false);
        if ($build->name() == $this->_invalidated) {
            return '~' . $name;
        }
        return $name;
    }

    /**
     * Modify a file name and append in the timestamp
     *
     * @param string $file The filename.
     * @param int $time The timestamp.
     * @return string The build filename to cache on disk.
     */
    protected function _timestampFile($file, $time)
    {
        if (!$time) {
            return $file;
        }
        $pos = strrpos($file, '.');
        $name = substr($file, 0, $pos);
        $ext = substr($file, $pos);
        return $name . '.v' . $time . $ext;
    }
}
