<?php
namespace AssetCompress\File;

use AssetCompress\File\FileInterface;

/**
 * Wrapper for local files that are used in asset targets.
 */
class Local implements FileInterface
{
    protected $path;

    public function __construct($path)
    {
        if (!is_file($path)) {
            throw new \RuntimeException("$path does not exist.");
        }
        $this->path = $path;
    }

    public function path()
    {
        return $this->path;
    }

    public function name()
    {
        return basename($this->path);
    }

    public function contents()
    {
        return file_get_contents($this->path);
    }

    public function modifiedTime()
    {
        return filemtime($this->path);
    }
}
