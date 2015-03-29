<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use AssetCompress\AssetFilter;
use AssetCompress\Utility\CssUtils;

/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * Requires nodejs and lesscss to be installed.
 *
 * @see http://lesscss.org/
 */
class LessCss extends AssetFilter
{

    protected $_settings = array(
        'ext' => '.less',
        'node' => '/usr/local/bin/node',
        'node_path' => '/usr/local/lib/node_modules',
        'paths' => [],
    );

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

            $lessFiles = [];
            foreach ($imports as $name) {
                if ('.css' === substr($name, -4)) {
                    // skip normal css imports
                    continue;
                }
                if ('.less' !== substr($name, -5)) {
                    $name .= '.less';
                }
                $lessFiles[] = $name;
            }
            foreach ($lessFiles as $import) {
                $path = $this->_findFile($import);
                $file = new Local($path);
                $newTarget = new AssetTarget('phony.css', [$file]);

                $children[] = $file;
                // Only recurse through less imports as css files are not
                // inlined by less.
                if ('.less' === substr($import, -5)) {
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

    /**
     * Runs `lessc` against any files that match the configured extension.
     *
     * @param string $filename The name of the input file.
     * @param string $input The content of the file.
     * @return string
     */
    public function input($filename, $input)
    {
        if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
            return $input;
        }

        $tmpfile = tempnam(TMP, 'asset_compress_less');
        $this->_generateScript($tmpfile, $input);

        $bin = $this->_settings['node'] . ' ' . $tmpfile;
        $env = array('NODE_PATH' => $this->_settings['node_path']);
        $return = $this->_runCmd($bin, '', $env);
        unlink($tmpfile);
        return $return;
    }

    protected function _generateScript($file, $input)
    {
        $text = <<<JS
var less = require('less'),
    util = require('util');

var parser = new less.Parser({ paths: %s });
parser.parse(%s, function (e, tree) {
    if (e) {
        less.writeError(e);
        process.exit(1)
    }
    util.print(tree.toCSS());
    process.exit(0);
});
JS;
        file_put_contents($file, sprintf($text, str_replace('\/*', '', json_encode($this->_settings['paths'])), json_encode($input)));
    }
}
