<?php
namespace AssetCompress\Output;

use AssetCompress\AssetTarget;
use AssetCompress\Output\FreshTrait;
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
    use FreshTrait;

    /**
     * The output path
     *
     * @var string
     */
    protected $path;

    /**
     * The theme currently being built.
     *
     * @var string
     */
    protected $theme;

    public function __construct($path, $theme = null)
    {
        $this->path = $path;
        $this->theme = $theme;
    }

    /**
     * Get the final build file name for a target.
     *
     * @param AssetTarget $target The target to get a name for.
     * @return string
     */
    public function buildFileName(AssetTarget $target)
    {
        $file = $target->name();
        if ($target->isThemed() && $this->theme) {
            $file = $this->theme . '-' . $file;
        }
        return $file;
    }

    public function write(AssetTarget $target, $contents)
    {
        $this->ensureDir();
        $buildName = $this->buildFileName($target);
        file_put_contents($this->path . $buildName, $contents);
    }

    /**
     * Create the output directory if it doesn't already exist.
     *
     * @return void
     */
    public function ensureDir()
    {
        $folder = new Folder($this->path, true);
        $folder->chmod($this->path, 0777);
    }

    /**
     * Get the cached result for a build target.
     *
     * @param AssetTarget $target The target to get content for.
     * @return string
     */
    public function read(AssetTarget $target)
    {
        $buildName = $this->buildFileName($target);
        return file_get_contents($this->path . $buildName);
    }

}
