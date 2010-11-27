<?php
App::import('Core', 'Folder');
App::import('Model', 'AssetCompress.JsFile');
App::import('Model', 'AssetCompress.CssFile');

class AssetBuildTask extends Shell {
	
	protected $_files = array();
	protected $_tokens = array();
	
	public $helperTokens = array(
		'$assetCompress', 'AssetCompress'
	);
	
	function build($paths) {
		$this->_collectFiles($paths);
		$this->_scanFiles();
		$this->_parse();
		$this->_buildFiles();
	}
	
	protected function _collectFiles($paths) {
		foreach ($paths as $path) {
			$Folder = new Folder($path);
			$files = $Folder->findRecursive('.*\.(ctp|thtml|inc|tpl)', true);
			$this->_files = array_merge($this->_files, $files);
		}
	}

/**
 * Scan each file for assetCompress helper calls.  Only pull out the 
 * calls to the helper.
 *
 * @return void
 */
	protected function _scanFiles() {
		$calls = array();
		foreach ($this->_files as $file) {
			$this->out('Scanning ' . $file . '...');

			$capturing = false;
		
			$content = file_get_contents($file);
			$tokens = token_get_all($content);
			foreach ($tokens as $token) {
				// found a helper method start grabbing tokens.
				if (is_array($token) && in_array($token[1], $this->helperTokens)) {
					$capturing = true;
					$call = array();
				}
				if ($capturing) {
					if (!is_array($token) && $token != ')') {
						continue;
					}
					$call[] = $token;
				}

				// end of function stop capturing
				if ($capturing && $token == ')') {
					$capturing = false;
					$calls[] = $call;
				}
			}
		}
		$this->_tokens = $calls;
	}

/**
 * Extract the file and build file names from the tokens.
 *
 * @return void
 */
	protected function _parse() {
		$fileMap = array();

		foreach ($this->_tokens as $call) {
			$method = $call[2][1];
			if (!in_array($method, array('css', 'script'))) {
				continue;
			}
			$args = array_slice($call, 3);
			$files = array();
			$build = false;

			$filename = trim($args[0][1], '\'"');
			$build = trim($args[2][1], '\'"');
			if (!isset($fileMap[$method][$build])) {
				$fileMap[$method][$build] = array();
			}
			$fileMap[$method][$build][] = $filename;
		}
		$this->_buildFiles = $fileMap;
	}
	
	protected function _buildFiles() {
		if (!empty($this->_buildFiles['css'])) {
			$Css = new CssFile();
			foreach ($this->_buildFiles['css'] as $target => $contents) {
				$this->out('Saving CSS file for ' . $target);
				$compress = $Css->process($contents);
				$Css->cache($target . '.css', $compress);
			}
		}
		if (!empty($this->_buildFiles['script'])) {
			$Js = new JsFile();
			foreach ($this->_buildFiles['script'] as $target => $contents) {
				$this->out('Saving Javascript file for ' . $target);
				$compress = $Js->process($contents);
				$Js->cache($target . '.js', $compress);
			}
		}
	}
}