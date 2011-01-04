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
				if ($capturing && $token == ';') {
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

			list($files, $build) = $this->_parseArgs($args);
		
			if (!isset($fileMap[$method][$build])) {
				$fileMap[$method][$build] = array();
			}
			$fileMap[$method][$build] = array_merge($fileMap[$method][$build], $files);
		}
		$this->_buildFiles = $fileMap;
		return $this->_buildFiles;
	}

/**
 * parses the arguments for a function call.
 *
 * @return array ($files, $buildFile)
 */
	function _parseArgs($tokens) {
		$files = array();
		$build = ':hash-default';
		$wasArray = false;

		while (true) {
			if (empty($tokens)) {
				break;
			}
			$token = array_shift($tokens);
			if ($token[0] == T_ARRAY) {
				$wasArray = true;
				$files = $this->_parseArray($tokens);
			}
			if ($token[0] == T_CONSTANT_ENCAPSED_STRING && $wasArray) {
				$build = trim($token[1], '"\'');
			} elseif ($token[0] == T_CONSTANT_ENCAPSED_STRING) {
				$files[] = trim($token[1], '"\'');
			}
		}
		if (!$wasArray && count($files) == 2) {
			$build = array_pop($files);
		}
		return array($files, $build);
	}

/**
 * Parses an array argument
 *
 * @return array Array of array members
 */
	function _parseArray(&$tokens) {
		$files = array();
		while (true) {
			if (empty($tokens)) {
				break;
			}
			$token = array_shift($tokens);
			if ($token[0] == T_CONSTANT_ENCAPSED_STRING) {
				$files[] = trim($token[1], '"\'');
			}
			// end of array
			if ($token[0] == ')') {
				break;
			}
		}
		return $files;
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
				if (strpos($target, ':hash') === 0) {
					$target = md5(implode('_', $contents));
				}
				$this->out('Saving CSS file for ' . $target);
				$compress = $Css->process($contents);
				$Css->cache($target . '.css', $compress);
			}
		}
		if (!empty($this->_buildFiles['script'])) {
			$Js = new JsFile();
			foreach ($this->_buildFiles['script'] as $target => $contents) {
				if (strpos($target, ':hash') === 0) {
					$target = md5(implode('_', $contents));
				}
				$this->out('Saving Javascript file for ' . $target);
				$compress = $Js->process($contents);
				$Js->cache($target . '.js', $compress);
			}
		}
	}
}