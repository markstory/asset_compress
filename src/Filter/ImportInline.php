<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetScanner;
use MiniAsset\Filter\ImportInline as BaseImportInline;

/**
 * CakePHP enhanced ImportInline filter.
 */
class ImportInline extends BaseImportInline
{
    /**
     * Use the CakePHP flavoured AssetScanner instead of the default one.
     *
     * This allows ImportInline to support theme & plugin prefixes.
     *
     * @return \AssetCompress\AssetScanner
     */
    protected function scanner()
    {
        if (isset($this->scanner)) {
            return $this->scanner;
        }
        $this->scanner = new AssetScanner(
            $this->_settings['paths'],
            isset($this->_settings['theme']) ? $this->_settings['theme'] : null
        );

        return $this->scanner;
    }
}
