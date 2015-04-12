<?php
namespace AssetCompress\Test\TestCase\Shell;

use AssetCompress\Shell\AssetCompressShell;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\Filesystem\Folder;
use MiniAsset\AssetConfig;

/**
 * AssetCompressShell test case.
 */
class AssetCompressShellTest extends TestCase
{

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $io = $this->getMock('Cake\Console\ConsoleIo', array(), array(), '', false);

        $this->Shell = new AssetCompressShell($io);
        $this->Shell->initialize();

        $this->testConfig = APP . 'config' . DS;

        $this->config = AssetConfig::buildFromIniFile(
            $this->testConfig . 'integration.ini',
            ['TEST_FILES' => APP, 'WEBROOT' => TMP]
        );
        $this->Shell->setConfig($this->config);
        mkdir(TMP . 'cache_js');
        mkdir(TMP . 'cache_css');
    }

    /**
     * Teardown method.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->Shell);
        $dir = new Folder(TMP . 'cache_js');
        $dir->delete();
        $dir = new Folder(TMP . 'cache_css');
        $dir->delete();
    }

    /**
     * Test building files from the config file.
     *
     * @return void
     */
    public function testBuildFiles()
    {
        $this->Shell->build();

        $this->assertTrue(file_exists(TMP . 'cache_css' . DS . 'all.css'), 'Css build missing');
        $this->assertTrue(file_exists(TMP . 'cache_js' . DS . 'libs.js'), 'Js build missing');
        $this->assertTrue(file_exists(TMP . 'cache_js' . DS . 'foo.bar.js'), 'Js build missing');
    }

    /**
     * Test building files from the config file.
     *
     * @return void
     */
    public function testBuildFilesWithTheme()
    {
        Plugin::load('Red');
        Plugin::load('Blue');
        $config = AssetConfig::buildFromIniFile(
            $this->testConfig . 'themed.ini',
            ['TEST_FILES' => APP, 'WEBROOT' => TMP]
        );
        $this->Shell->setConfig($config);
        $this->Shell->build();

        $this->assertFileExists(TMP . 'cache_css' . DS . 'Blue-themed.css', 'Css build missing');
        $this->assertFileExists(TMP . 'cache_css' . DS . 'Red-themed.css', 'Css build missing');
        $this->assertFileExists(TMP . 'cache_css' . DS . 'Blue-combined.css', 'Css build missing');
        $this->assertFileExists(TMP . 'cache_css' . DS . 'Red-combined.css', 'Css build missing');
    }

    /**
     * Test clearing build files.
     *
     * @return void
     */
    public function testClear()
    {
        $config = AssetConfig::buildFromIniFile(
            $this->testConfig . 'integration.ini',
            ['TEST_FILES' => APP, 'WEBROOT' => TMP]
        );
        $this->Shell->setConfig($config);

        $files = [
            TMP . 'cache_css/all.css',
            TMP . 'cache_css/all.v12354.css',
            TMP . 'cache_js/libs.js',
            TMP . 'cache_js/libs.v12354.js'
        ];
        foreach ($files as $file) {
            touch($file);
        }

        $this->Shell->clear();

        foreach ($files as $file) {
            $this->assertFileNotExists($file, "$file was not cleared");
        }
    }

    /**
     * Test building files from the config file.
     *
     * @return void
     */
    public function testClearFilesWithTheme()
    {
        Plugin::load('Red');
        Plugin::load('Blue');
        $files = [
            TMP . 'cache_css/Blue-themed.css',
            TMP . 'cache_css/Red-themed.css',
        ];
        foreach ($files as $file) {
            touch($file);
        }

        $config = AssetConfig::buildFromIniFile(
            $this->testConfig . 'themed.ini',
            ['TEST_FILES' => APP, 'WEBROOT' => TMP]
        );
        $this->Shell->setConfig($config);
        $this->Shell->clear();
        foreach ($files as $file) {
            $this->assertFileNotExists($file);
        }
    }

    /**
     * Test clearing build files doesn't nuke unknown files.
     *
     * @return void
     */
    public function testClearIgnoreUnmanagedFiles()
    {
        $config = AssetConfig::buildFromIniFile(
            $this->testConfig . 'integration.ini',
            ['TEST_FILES' => APP, 'WEBROOT' => TMP]
        );
        $this->Shell->setConfig($config);

        $files = [
            TMP . 'cache_js/nope.js',
            TMP . 'cache_js/nope.v12354.js'
        ];
        foreach ($files as $file) {
            touch($file);
        }

        $this->Shell->clear();

        foreach ($files as $file) {
            $this->assertTrue(file_exists($file), "$file should not be cleared");
        }
    }
}
