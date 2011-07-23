<?php

App::import('Libs', 'AssetCompress.AssetCompiler');
App::import('Libs', 'AssetCompress.AssetConfig');

class AssetCompilerTest extends CakeTestCase {

	function setUp() {
		$this->_pluginPath = App::pluginPath('AssetCompress');
		$testFile = $this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'config' . DS . 'config.ini';

		AssetConfig::clearAllCachedKeys();
		$this->config = AssetConfig::buildFromIniFile($testFile);
		$this->config->paths('js', array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS,
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'js' . DS . '*'
		));
		$this->config->paths('css', array(
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'css' . DS,
			$this->_pluginPath . 'tests' . DS . 'test_files' . DS . 'css' . DS . '*'
		));
		$this->Compiler = new AssetCompiler($this->config);
	}

	function testConcatenationJavascript() {
		$this->config->filters('js', null, array());
		$this->config->addTarget('template.js', array('classes/base_class.js', 'classes/template.js'));
		$result = $this->Compiler->generate('template.js');
		$expected = <<<TEXT
var BaseClass = new Class({

});//= require "base_class"
var Template = new Class({

});
TEXT;
		$this->assertEqual($result, $expected);
	}

	function testConcatenationCss() {
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
		$this->assertEqual($result, $expected);
	}

	function testCombiningWithOtherExtensions() {
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
		$this->assertEqual($result, $expected);
	}

}
