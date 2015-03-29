<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetFilter;
use AssetCompress\Filter\CssDependencyTrait;
use lessc;

/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * Requires lessphp to be installed via composer.
 *
 * @see http://leafo.net/lessphp
 */
class LessPHP extends AssetFilter
{
    use CssDependencyTrait;

    protected $_settings = array(
        'ext' => '.less',
        'paths' => [],
    );

    /**
     * Runs `lessc` against any files that match the configured extension.
     *
     * @param string $filename The name of the input file.
     * @param string $input The content of the file.
     * @throws Exception
     * @return string
     */
    public function input($filename, $input)
    {
        if (substr($filename, strlen($this->_settings['ext']) * -1) !== $this->_settings['ext']) {
            return $input;
        }
        if (!class_exists('lessc')) {
            throw new \Exception('Cannot not load "lessc" class. Make sure it is installed.');
        }
        $lc = new lessc($filename);
        return $lc->parse();
    }
}
