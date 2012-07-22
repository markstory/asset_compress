<?php
App::uses('Shell', 'Console');
App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('AssetCompiler', 'AssetCompress.Lib');
App::uses('AssetCache', 'AssetCompress.Lib');

App::uses('Folder', 'Utility');

class AssetBuildTask extends Shell {

	protected $_Config;

	protected $_themes = array();

	protected $_files = array();

	protected $_tokens = array();

/**
 * Array of tokens that indicate a helper call.
 *
 * @var array
 */
	public $helperTokens = array(
		'$assetCompress', 'AssetCompress'
	);

/**
 * Array of helper methods to look for.
 *
 * @var array
 */
	protected $_methods = array('addCss', 'addScript');

/**
 * Set the Configuration object that will be used.
 *
 * @return void
 */
	public function setConfig(AssetConfig $Config) {
		$this->_Config = $Config;
		$this->Compiler = new AssetCompiler($this->_Config);
		$this->Cacher = new AssetCache($this->_Config);
	}

	public function setThemes($themes) {
		$this->_themes = $themes;
	}

/**
 * Build all the files declared in the Configuration object.
 *
 * @return void
 */
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

/**
 * Generate dynamically declared build targets in a set of paths.
 *
 * @param array $paths Array of paths to scan for dynamic builds
 * @return void
 */
	public function buildDynamic($paths) {
		$this->_collectFiles($paths);
		$this->scanFiles();
		$this->parse();
		$this->_buildFiles();
	}

/**
 * Accessor for testing, sets files.
 *
 * @param array $files Array of files to scan
 * @return void
 */
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
	public function scanFiles() {
		$calls = array();
		foreach ($this->_files as $file) {
			$this->out('Scanning ' . $file . '...', 1, Shell::VERBOSE);

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
	public function parse() {
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
	protected function _parseArgs($tokens) {
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
	protected function _parseArray(&$tokens) {
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
		foreach ($this->_methods as $method) {
			if (empty($this->_buildFiles[$method])) {
				continue;
			}
			foreach ($this->_buildFiles[$method] as $target => $contents) {
				if (strpos($target, ':hash') === 0) {
					$target = md5(implode('_', $contents));
				}
				$ext = $method == 'addScript'  ? '.js' : '.css';
				$target = $this->_addExt($target, $ext);
				$this->_Config->files($target, $contents);
				$this->_buildTarget($target);
			}
		}
	}

/**
 * Generate and save the cached file for a build target.
 *
 * @param string $build The build to generate.
 * @return void
 */
	protected function _buildTarget($build) {
		if ($this->_Config->isThemed($build)) {
			foreach ($this->_themes as $theme) {
				$this->_Config->theme($theme);
				$this->_generateFile($build);
			}
		} else {
			$this->_generateFile($build);
		}
	}

	protected function _generateFile($build) {
		$name = $this->Cacher->buildFileName($build);
		if ($this->Cacher->isFresh($build) && empty($this->params['force'])) {
			$this->out('<info>Skip building</info> ' . $name . ' existing file is still fresh.');
			return;
		}
		$this->Cacher->setTimestamp($build, 0);
		$name = $this->Cacher->buildFileName($build);
		try {
			$this->out('<success>Saving file</success> for ' . $name);
			$contents = $this->Compiler->generate($build);
			$this->Cacher->write($build, $contents);
		} catch (Exception $e) {
			$this->err('Error: ' . $e->getMessage());
		}
	}

/**
 * Adds an extension if the file doesn't already end with it.
 *
 * @param string $file Filename
 * @param string $ext Extension with .
 * @return string
 */
	protected function _addExt($file, $ext) {
		if (substr($file, strlen($ext) * -1) !== $ext) {
			$file .= $ext;
		}
		return $file;
	}
}
