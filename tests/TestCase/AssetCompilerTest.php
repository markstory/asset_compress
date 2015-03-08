<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use AssetCompress\AssetTarget;
use AssetCompress\Factory;
use AssetCompress\File\Local;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class AssetCompilerTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->_testFiles = APP;
        $this->_themeConfig = $this->_testFiles . 'config' . DS . 'themed.ini';
        $this->_pluginConfig = $this->_testFiles . 'config' . DS . 'plugins.ini';

        $testFile = $this->_testFiles . 'config' . DS . 'integration.ini';

        $this->config = AssetConfig::buildFromIniFile($testFile);
        $this->config->paths('js', null, array(
            $this->_testFiles . 'js' . DS,
            $this->_testFiles . 'js' . DS . '*',
        ));
        $this->config->paths('css', null, array(
            $this->_testFiles . 'css' . DS,
            $this->_testFiles . 'css' . DS . '*',
        ));
    }

    protected function instance()
    {
        $factory = new Factory($this->config);
        return $factory->compiler();
    }

    public function testConcatenationJavascript()
    {
        $files = [
            new Local(APP . 'js/classes/base_class.js'),
            new Local(APP . 'js/classes/template.js'),
        ];
        $target = new AssetTarget(TMP . 'template.js', $files);
        $compiler = $this->instance();
        $result = $compiler->generate($target);
        $expected = <<<TEXT
var BaseClass = new Class({

});

//= require "base_class"
var Template = new Class({

});
TEXT;
        $this->assertEquals($expected, $result);
    }

    public function testConcatenationCss()
    {
        $files = [
            new Local(APP . 'css/reset/reset.css'),
            new Local(APP . 'css/nav.css'),
        ];
        $target = new AssetTarget(TMP . 'all.css', $files);
        $compiler = $this->instance();
        $result = $compiler->generate($target);
        $expected = <<<TEXT
* {
    margin:0;
    padding:0;
}
@import url("reset/reset.css");
#nav {
    width:100%;
}
TEXT;
        $this->assertEquals($expected, $result);
    }

    public function testCombiningWithOtherExtensions()
    {
        $files = [
            new Local(APP . 'css/other.less'),
            new Local(APP . 'css/nav.css'),
        ];
        $target = new AssetTarget(TMP . 'all.css', $files);
        $compiler = $this->instance();
        $result = $compiler->generate($target);
        $expected = <<<TEXT
#footer
    color: blue;

@import url("reset/reset.css");
#nav {
    width:100%;
}
TEXT;
        $this->assertEquals($expected, $result);
    }

    public function testCombineThemeFile()
    {
        Plugin::load('Blue');
        $this->config->theme('blue');

        $files = [
            new Local(APP . 'Plugin/Blue/webroot/theme.css'),
        ];
        $target = new AssetTarget(TMP . 'themed.css', $files, [], [], true);
        $compiler = $this->instance();

        $result = $compiler->generate($target);
        $expected = <<<TEXT
body {
    color: blue !important;
}
TEXT;
        $this->assertEquals($expected, $result);
    }

    public function testCombineWithFilters()
    {
        $files = [
            new Local(APP . 'js/classes/base_class_two.js'),
        ];
        $target = new AssetTarget(TMP . 'class.js', $files, ['Sprockets']);
        $compiler = $this->instance();

        $result = $compiler->generate($target);
        $expected = <<<TEXT
var BaseClass = new Class({

});

var BaseClassTwo = BaseClass.extend({

});
TEXT;
        $this->assertEquals($expected, $result);
    }
}
