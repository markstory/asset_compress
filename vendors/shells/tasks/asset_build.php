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

	public function setFiles($files) {
		$this->_files = $files;
	}

/**
 * Collects the files to scan and generate build files for.
 *
 * @param array $paths 
 */
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
	function _scanFiles() {
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
		return $this->_tokens;
	}

/**
 * Extract the file and build file names from the tokens.
 *
 * @return void
 */
	function _parse() {
		$fileMap = array();

		foreach ($this->_tokens as $call) {
			$method = $call[2][1];
			if (!in_array($method, array('css', 'script'))) {
				continue;
			}
	
			$args = array_slice($call, 3);

			$files = array();
			$build = ':hash-default';

			foreach ($args as $arg) {
				if (
					$arg[0] == T_ARRAY ||
					$arg[0] == T_WHITESPACE ||
					$arg[0] == ',' ||
					$arg[0] == '(' ||
					$arg[0] == ')'
				) {
					continue;
				}
				// array checks here.

				$files[] = trim($arg[1], '\'"');
			}
			if (count($files) == 2) {
				$build = array_pop($files);
			}
		
			if (!isset($fileMap[$method][$build])) {
				$fileMap[$method][$build] = array();
			}
			$fileMap[$method][$build] = array_merge($fileMap[$method][$build], $files);
		}
		$this->_buildFiles = $fileMap;
		return $this->_buildFiles;
	}

/**
 * Generate the build files for css and scripts.
 *
 * @return void
 */
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