<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetFilter;
use AssetCompress\AssetScanner;
use Cake\Utility\Hash;
use RuntimeException;

/**
 * A preprocessor that inlines files referenced by
 * @import() statements in css files.
 */
class ImportInline extends AssetFilter
{

    protected $_pattern = '/^\s*@import\s*(?:(?:([\'"])([^\'"]+)\\1)|(?:url\(([\'"])([^\'"]+)\\3\)));/m';

    protected $scanner = null;

    protected $_loaded = array();

    public function settings(array $settings = null)
    {
        parent::settings($settings);
        $this->scanner = new AssetScanner(
            (array)Hash::get($this->_settings, 'paths'),
            Hash::get($this->_settings, 'theme')
        );
        return $this->_settings;
    }

    /**
     * Preprocesses CSS files and replaces @import statements.
     *
     * @param string $filename
     * @param string $content
     * @return The processed file.
     */
    public function input($filename, $content)
    {
        return preg_replace_callback(
            $this->_pattern,
            array($this, '_replace'),
            $content
        );
    }

    /**
     * Does file replacements.
     *
     * @param array $matches
     * @throws RuntimeException
     */
    protected function _replace($matches)
    {
        $required = empty($matches[2]) ? $matches[4] : $matches[2];
        $filename = $this->scanner->find($required);
        if (!$filename) {
            throw new \RuntimeException(sprintf('Could not find dependency "%s"', $required));
        }
        if (empty($this->_loaded[$filename])) {
            return $this->input($filename, file_get_contents($filename));
        }
        return '';
    }
}
