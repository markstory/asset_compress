<?php
App::import('Lib', 'AssetCompress.AssetConfig');
App::import('Lib', 'AssetCompress.AssetCache');
App::import('Lib', 'AssetCompress.AssetCompiler');

App::import('Core', 'Folder');

class AssetBuildTask extends Shell {
	
	protected $_Config;
	protected $_files = array();
	protected $_tokens = array();
	
	public $helperTokens = array(
		'$assetCompress', 'AssetCompress'
	);
	protected $_methods = array('addCss', 'addScript');

	public function setConfig(AssetConfig $Config) {
		$this->_Config = $Config;
	}

	public function buildIni() {
		$targets = $this->_Config->targets('js');
		foreach ($targets as $t) {
			$this->_buildTarget($t);
		}
		$targets = $this->_Config->targets('css');
		foreach ($targets as $t) {
			$this->_buildTarget($t);
		}
	}

	function buildDynamic($paths) {
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
			if (!in_array($method, $this->_methods)) {
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
		if (!empty($this->_buildFiles['addCss'])) {
			foreach ($this->_buildFiles['addCss'] as $target => $contents) {
				if (strpos($target, ':hash') === 0) {
					$target = md5(implode('_', $contents));
				}
				$this->_Config->files($target, $contents);
				$this->_buildTarget($target);
			}
		}
		if (!empty($this->_buildFiles['addScript'])) {
			foreach ($this->_buildFiles['addScript'] as $target => $contents) {
				if (strpos($target, ':hash') === 0) {
					$target = md5(implode('_', $contents));
				}
				$this->_Config->files($target, $contents);
				$this->_buildTarget($target);
			}
		}
	}

	protected function _buildTarget($build) {
		$this->out('Saving file for ' . $build);
		$Compiler = new AssetCompiler($this->_Config);
		$Cacher = new AssetCache($this->_Config);
		try {
			$contents = $Compiler->generate($build);
			$Cacher->write($build, $contents);
		} catch (Exception $e) {
			$this->err('Error: ' . $e->getMessage());
		}
	}
}
