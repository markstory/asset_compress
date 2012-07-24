<?php
App::uses('AssetCompiler', 'AssetCompress.Lib');
App::uses('AssetConfig', 'AssetCompress.Lib');

class AssetCompilerTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$this->_testFiles = App::pluginPath('AssetCompress') . 'Test' . DS . 'test_files' . DS;
		$this->_themeConfig = $this->_testFiles . 'Config' . DS . 'themed.ini';
		$this->_pluginConfig = $this->_testFiles . 'Config' . DS . 'plugins.ini';

		$testFile = $this->_testFiles . 'Config' . DS . 'config.ini';

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($testFile);
		$this->config->paths('js', array(
			$this->_testFiles . 'js' . DS,
			$this->_testFiles . 'js' . DS . '*',
		));
		$this->config->paths('css', array(
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

});//= require "base_class"
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
}@import url("reset/reset.css");
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
		App::build(array(
			'View' => array($this->_testFiles . 'View' . DS)
		));
		$Config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$Config->paths('css', array(
			$this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS . '**'
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

	public function testCombineThemeFileWithNonTheme() {
		App::build(array(
			'View' => array($this->_testFiles . 'View' . DS)
		));
		$Config = AssetConfig::buildFromIniFile($this->_themeConfig);
		$Config->paths('css', array(
			$this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS . '**'
		));
		$Config->theme('red');
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('combined.css');
		$expected = <<<TEXT
@import url("reset/reset.css");
#nav {
	width:100%;
}body {
	color: red !important;
}
TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testCompilePluginFiles() {
		App::build(array(
			'Plugin' => array($this->_testFiles . 'Plugin' . DS)
		));
		CakePlugin::load('TestAsset');

		$Config = AssetConfig::buildFromIniFile($this->_pluginConfig);
		$Config->paths('css', array(
			$this->_pluginPath . 'Test' . DS . 'test_files' . DS . 'css' . DS . '**'
		));
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('plugins.css');
		$expected = <<<TEXT
@import url("reset/reset.css");
#nav {
	width:100%;
}.plugin-box {
	color: orange;
}
TEXT;
		$this->assertEquals($expected, $result);
	}

	public function testCompileRemoteFiles() {
		$Config = AssetConfig::buildFromIniFile($this->_testFiles . 'Config' . DS . 'remote_file.ini');
		$Compiler = new AssetCompiler($Config);

		$result = $Compiler->generate('remote_file.js');
		$this->assertContains('jQuery', $result);
	}

}
