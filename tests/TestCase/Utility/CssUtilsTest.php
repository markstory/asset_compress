<?php
namespace AssetCompress\Test\TestCase\Utility;

use AssetCompress\Utility\CssUtils;
use PHPUnit_Framework_TestCase;

/**
 * Tests for CssUtils
 */
class CssUtilsTest extends PHPUnit_Framework_TestCase
{
    public function testExtractImports()
    {
        $css = <<<CSS
@import     'first.css';
@import 'second.css';
@import "third.css";
@import '../../relative-path.css';
@import "http://example.com/dir/absolute-path.css";
CSS;
        $result = CssUtils::extractImports($css);
        $this->assertCount(5, $result);
        $expected = [
            'first.css',
            'second.css',
            'third.css',
            '../../relative-path.css',
            'http://example.com/dir/absolute-path.css'
        ];
        $this->assertEquals($expected, $result);
    }
}
