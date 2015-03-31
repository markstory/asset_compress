<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetFilter;
use CssMin;
use RuntimeException;

/**
 * CssMin filter.
 *
 * Allows you to filter Css files through CssMin. You need to install CssMin with composer.
 */
class CssMinFilter extends AssetFilter
{

    /**
     * Apply CssMin to $content.
     *
     * @param string $filename target filename
     * @param string $content Content to filter.
     * @throws Exception
     * @return string
     */
    public function output($filename, $content)
    {
        if (!class_exists('CssMin')) {
            throw new RuntimeException('Cannot not load filter class "CssMin". Ensure you have it installed.');
        }
        return CssMin::minify($content);
    }
}
