<?php
namespace AssetCompress\Utility;

/**
 * Utility class for CSS files.
 */
class CssUtils
{
    const IMPORT_PATTERN = '/^\s*@import\s*(?:(?:([\'"])([^\'"]+)\\1)|(?:url\(([\'"])([^\'"]+)\\3\)));/m';

    /**
     * Extract the urls in import directives.
     *
     * @param string $css The CSS to parse.
     * @return array An array of CSS files that were used in imports.
     */
    public static function extractImports($css)
    {
        $imports = [];
        preg_match_all(static::IMPORT_PATTERN, $css, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return $imports;
        }
        foreach ($matches as $match) {
            $url = empty($matches[2]) ? $match[4] : $match[2];
            $imports[] = $url;
        }
        return $imports;
    }
}
