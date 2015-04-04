<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use AssetCompress\Utility\CssUtils;

trait CssDependencyTrait
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(AssetTarget $target)
    {
        $children = [];
        foreach ($target->files() as $file) {
            $imports = CssUtils::extractImports($file->contents());
            if (empty($imports)) {
                continue;
            }

            $ext = $this->_settings['ext'];
            $extLength = strlen($ext);

            $deps = [];
            foreach ($imports as $name) {
                if ('.css' === substr($name, -4)) {
                    // skip normal css imports
                    continue;
                }
                if ($ext !== substr($name, -$extLength)) {
                    $name .= $ext;
                }
                $deps[] = $name;
            }
            foreach ($deps as $import) {
                $path = $this->_findFile($import);
                $file = new Local($path);
                $newTarget = new AssetTarget('phony.css', [$file]);

                $children[] = $file;
                // Only recurse through non-css imports as css files are not
                // inlined by less/sass.
                if ($ext === substr($import, -$extLength)) {
                    $children = array_merge($children, $this->getDependencies($newTarget));
                }
            }
        }
        return $children;
    }

    /**
     * Attempt to locate a file in the configured paths.
     *
     * @param string $file The file to find.
     * @return string The resolved file.
     */
    protected function _findFile($file)
    {
        foreach ($this->_settings['paths'] as $path) {
            if (file_exists($path . $file)) {
                return $path . $file;
            }
        }
        return $file;
    }
}
