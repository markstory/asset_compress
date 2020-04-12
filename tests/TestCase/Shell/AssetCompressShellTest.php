<?php
declare(strict_types=1);

namespace AssetCompress\Test\TestCase\Shell;

use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\ConsoleIntegrationTestTrait;

/**
 * AssetCompressShell test case.
 */
class AssetCompressShellTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setup method.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->testConfig = APP . 'config' . DS;
        mkdir(WWW_ROOT . 'cache_js');
        mkdir(WWW_ROOT . 'cache_css');

        $this->loadPlugins(['AssetCompress']);
    }

    /**
     * Teardown method.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->Shell);
        $dir = new Folder(WWW_ROOT . 'cache_js');
        $dir->delete();
        $dir = new Folder(WWW_ROOT . 'cache_css');
        $dir->delete();
    }

    /**
     * Test building files from the config file.
     *
     * @return void
     */
    public function testBuildFiles()
    {
        $config = $this->testConfig. 'integration.ini';
        $this->exec("asset_compress build --config {$config}");
        $this->assertExitSuccess();

        $this->assertTrue(file_exists(WWW_ROOT . 'cache_css' . DS . 'all.css'), 'Css build missing');
        $this->assertTrue(file_exists(WWW_ROOT . 'cache_js' . DS . 'libs.js'), 'Js build missing');
        $this->assertTrue(file_exists(WWW_ROOT . 'cache_js' . DS . 'foo.bar.js'), 'Js build missing');
    }

    /**
     * Test building files from the config file.
     *
     * @return void
     */
    public function testBuildFilesWithTheme()
    {
        $this->loadPlugins(['Red', 'Blue']);

        $config = $this->testConfig . 'themed.ini';
        $this->exec("asset_compress build --config {$config}");
        $this->assertExitSuccess();

        $this->assertFileExists(WWW_ROOT . 'cache_css' . DS . 'Blue-themed.css', 'Css build missing');
        $this->assertFileExists(WWW_ROOT . 'cache_css' . DS . 'Red-themed.css', 'Css build missing');
        $this->assertFileExists(WWW_ROOT . 'cache_css' . DS . 'Blue-combined.css', 'Css build missing');
        $this->assertFileExists(WWW_ROOT . 'cache_css' . DS . 'Red-combined.css', 'Css build missing');
    }

    /**
     * Test clearing build files.
     *
     * @return void
     */
    public function testClear()
    {
        $files = [
            WWW_ROOT . 'cache_css/all.css',
            WWW_ROOT . 'cache_css/all.v12354.css',
            WWW_ROOT . 'cache_js/libs.js',
            WWW_ROOT . 'cache_js/libs.v12354.js',
        ];
        foreach ($files as $file) {
            touch($file);
        }
        $config = $this->testConfig . 'integration.ini';
        $this->exec("asset_compress clear --config {$config}");
        $this->assertExitSuccess();

        foreach ($files as $file) {
            $this->assertFileNotExists($file, "$file was not cleared");
        }
    }

    /**
     * Test clearing themed files.
     *
     * @return void
     */
    public function testClearFilesWithTheme()
    {
        $this->loadPlugins(['Red', 'Blue']);
        $files = [
            WWW_ROOT . 'cache_css/Blue-themed.css',
            WWW_ROOT . 'cache_css/Red-themed.css',
        ];
        foreach ($files as $file) {
            touch($file);
        }
        $config = $this->testConfig . 'themed.ini';
        $this->exec("asset_compress clear --config {$config}");
        $this->assertExitSuccess();

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
        $files = [
            WWW_ROOT . 'cache_js/nope.js',
            WWW_ROOT . 'cache_js/nope.v12354.js',
        ];
        foreach ($files as $file) {
            touch($file);
        }
        $config = $this->testConfig . 'integration.ini';
        $this->exec("asset_compress clear --config {$config}");
        $this->assertExitSuccess();

        foreach ($files as $file) {
            $this->assertFileExists($file, "$file should not be cleared");
        }
    }
}
