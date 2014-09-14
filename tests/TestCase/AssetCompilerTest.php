<?php
namespace AssetCompress\Test\TestCase;

use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

class AssetCompilerTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$this->_testFiles = APP;
		$this->_themeConfig = $this->_testFiles . 'config' . DS . 'themed.ini';
		$this->_pluginConfig = $this->_testFiles . 'config' . DS . 'plugins.ini';

		$testFile = $this->_testFiles . 'config' . DS . 'config.ini';

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($testFile);
		$this->config->paths('js', null, array(
			$this->_testFiles . 'js' . DS,
			$this->_testFiles . 'js' . DS . '*',
		));
		$this->config->paths('css', null, array(
			$this->_testFiles . 'css' . DS,
			$this->_testFiles . 'css' . DS . '*',
		));
		$this->Compiler = new AssetCompiler($this->config);
	}

	public function testConcatenationJavascript() {
		$this->config->filters('js', null, array());
		$this->config->addTarget('template.js', array('classes/base_class.js', 'classes/template.js'));
		$result = $this->Compiler->generate('template.js');
		$expected = <<<TEXT
var BaseClass = new Class({

});
//= require "base_class"
var Template = new Class({

});
TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testConcatenationCss() {
		$this->config->filters('css', null, array());
		$this->config->addTarget('all.css', array('reset/reset.css', 'nav.css'));
		$result = $this->Compiler->generate('all.css');
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

	public function testCombiningWithOtherExtensions() {
		$this->config->filters('css', null, array());
		$this->config->addTarget('all.css', array('other.less', 'nav.css'));
		$result = $this->Compiler->generate('all.css');
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

	public function testCombineThemeFile() {
		Plugin::load('Blue');

		$Config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$Config->paths('css', null, array(
			APP . DS . 'css' . DS . '**'
		));
		$Config->theme('blue');
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('themed.css');
		$expected = <<<TEXT
body {
	color: blue !important;
}
TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testMultipleThemeGeneration() {
		Plugin::load('Blue');
		Plugin::load('Red');

		$Config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$Config->paths('css', null, array(
			APP . 'css' . DS . '**'
		));
		$Config->theme('blue');
		$Compiler = new AssetCompiler($Config);
		// Generate the blue file.
		$Compiler->generate('themed.css');

		$Config->theme('red');
		$result = $Compiler->generate('themed.css');
		$expected = <<<TEXT
body {
	color: red !important;
}
TEXT;
		$this->assertEquals($expected, $result, 'red should not contain blue.');
	}

	public function testCombineThemeFileWithNonTheme() {
		Plugin::load('Red');
		$Config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$Config->paths('css', null, array(
			APP . 'css' . DS . '**'
		));
		$Config->theme('red');
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('combined.css');
		$expected = <<<TEXT
@import url("reset/reset.css");
#nav {
	width:100%;
}

body {
	color: red !important;
}
TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testCompilePluginFiles() {
		Plugin::load('TestAsset');

		$Config = AssetConfig::buildFromIniFile($this->_pluginConfig);
		$Config->paths('css', null, array(
			APP . 'css' . DS . '**'
		));
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('plugins.css');
		$expected = <<<TEXT
@import url("reset/reset.css");
#nav {
	width:100%;
}

.plugin-box {
	color: orange;
}
TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testCompileRemoteFiles() {
		$Config = AssetConfig::buildFromIniFile($this->_testFiles . 'config' . DS . 'remote_file.ini');
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('remote_file.js');
		$this->assertContains('jQuery', $result);
	}

}
