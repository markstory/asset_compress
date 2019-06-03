<?php
declare(strict_types=1);

namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetScanner;
use Cake\TestSuite\TestCase;

class AssetScannerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->_testFiles = APP;
        $paths = [
            $this->_testFiles . 'js' . DS,
            $this->_testFiles . 'js' . DS . 'classes' . DS,
        ];
        $this->Scanner = new AssetScanner($paths);
    }

    public function testFindResolveThemePaths()
    {
        $this->loadPlugins(['Blue']);
        $paths = [
            $this->_testFiles . 'css' . DS,
        ];
        $scanner = new AssetScanner($paths, 'Blue');
        $result = $scanner->find('t:theme.css');
        $expected = $this->_testFiles . 'Plugin' . DS . 'Blue' . DS . 'webroot' . DS . 'theme.css';
        $this->assertEquals($expected, $result);

        $result = $scanner->find('theme:theme.css');
        $this->assertEquals($expected, $result);
    }

    public function testFindResolvePluginPaths()
    {
        $this->loadPlugins(['TestAsset']);

        $paths = [
            $this->_testFiles . 'css' . DS,
        ];
        $scanner = new AssetScanner($paths);
        $result = $scanner->find('p:TestAsset:plugin.css');
        $expected = $this->_testFiles . 'Plugin' . DS . 'TestAsset' . DS . 'webroot' . DS . 'plugin.css';
        $this->assertEquals($expected, $result);

        $result = $scanner->find('plugin:TestAsset:plugin.css');
        $this->assertEquals($expected, $result);
    }
}
