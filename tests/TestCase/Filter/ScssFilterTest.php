<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use AssetCompress\Filter\ScssFilter;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class ScssFilterTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->_cssDir = APP . 'css' . DS;
        $this->filter = new ScssFilter();
        $this->filter->settings([
            'paths' => [$this->_cssDir]
        ]);
    }

    public function testParsing()
    {
        $this->skipIf(DS === '\\', 'Requires ruby and sass rubygem to be installed');
        $hasSass = `which sass`;
        $this->skipIf(empty($hasSass), 'Requires ruby and sass to be installed');
        $this->filter->settings(array('sass' => trim($hasSass)));

        $content = file_get_contents($this->_cssDir . 'test.scss');
        $result = $this->filter->input($this->_cssDir . 'test.scss', $content);
        $expected = file_get_contents($this->_cssDir . 'compiled_scss.css');
        $this->assertEquals($expected, $result);
    }

    public function testGetDependencies()
    {
        $files = [
            new Local($this->_cssDir . 'test.scss')
        ];
        $target = new AssetTarget('test.css', $files);
        $result = $this->filter->getDependencies($target);

        $this->assertCount(1, $result);
        $this->assertEquals('colors.scss', $result[0]->name());
    }
}
