<?php
namespace AssetCompress\Test\TestCase\Filter;

use AssetCompress\AssetTarget;
use AssetCompress\File\Local;
use AssetCompress\Filter\LessCss;
use Cake\Core\Plugin;
use PHPUnit_Framework_TestCase;

class LessCssTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->_cssDir = APP . 'css' . DS;
        $this->filter = new LessCss();
        $this->filter->settings([
            'paths' => [$this->_cssDir]
        ]);
    }

    public function testGetDependencies()
    {
        $files = [
            new Local($this->_cssDir . 'other.less')
        ];
        $target = new AssetTarget('test.css', $files);
        $result = $this->filter->getDependencies($target);

        $this->assertCount(2, $result);
        $this->assertEquals('base.less', $result[0]->name());
        $this->assertEquals('colors.less', $result[1]->name());
    }
}
