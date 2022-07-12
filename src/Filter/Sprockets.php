<?php
declare(strict_types=1);

namespace AssetCompress\Filter;

use AssetCompress\AssetScanner;
use MiniAsset\Filter\Sprockets as BaseSprockets;

/**
 * CakePHP enhanced Sprockets filter.
 */
class Sprockets extends BaseSprockets
{
    /**
     * Use the CakePHP flavoured AssetScanner instead of the default one.
     *
     * This allows Sprockets to support theme & plugin prefixes.
     *
     * @return \AssetCompress\AssetScanner
     */
    protected function _scanner(): AssetScanner
    {
        if (isset($this->_scanner)) {
            return $this->_scanner;
        }
        $this->_scanner = new AssetScanner(
            $this->_settings['paths'],
            $this->_settings['theme'] ?? null
        );

        return $this->_scanner;
    }
}
