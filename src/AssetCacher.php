<?php
namespace AssetCompress;

use AssetCompress\AssetTarget;
use Cake\Filesystem\Folder;

/**
 * Writes temporary output files for assets.
 *
 * Similar to AssetWriter except this class takes a more simplistic
 * approach to writing cache files. It also provides ways to read existing
 * cache files.
 */
class AssetCacher
{
    protected $path;
    protected $theme;

    public function __construct($path, $theme = null)
    {
        $this->path = $path;
        $this->theme = $theme;
    }

    public function buildFileName($target)
    {
        $file = $target->name();
        if ($target->isThemed() && $this->theme) {
            $file = $this->theme . '-' . $file;
        }
        return $file;
    }

    public function isFresh(AssetTarget $target)
    {
        $buildName = $this->buildFileName($target);
        $buildFile = $this->path . $buildName;

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

    public function write(AssetTarget $target, $contents)
    {
        $this->ensureDir();
        $buildName = $this->buildFileName($target);
        file_put_contents($this->path . $buildName, $contents);
    }

    public function ensureDir()
    {
        $folder = new Folder($this->path, true);
        $folder->chmod($this->path, 0777);
    }

    public function read(AssetTarget $target)
    {
        $buildName = $this->buildFileName($target);
        return file_get_contents($this->path . $buildName);
    }

}
