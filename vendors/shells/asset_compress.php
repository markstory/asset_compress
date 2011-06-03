<?php
App::import('Lib', 'AssetCompress.AssetConfig');
App::import('Lib', 'AssetCompress.AssetCompiler');
App::import('Lib', 'AssetCompress.AssetCache');

/**
 * Asset Compress Shell
 *
 * Assists in clearing and creating the build files this plugin makes.
 *
 * @package AssetCompress
 */
class AssetCompressShell extends Shell {

	public $tasks = array('AssetBuild');

/**
 * Create the configuration object used in other classes.
 *
 */
	public function startup() {
		parent::startup();
		$config = null;
		if (isset($this->params['config'])) {
			$config = $this->params['config'];
		}
		$this->_Config = AssetConfig::buildFromIniFile($config);
	}

/**
 * Builds all the files defined in the build file.
 *
 * @return void
 */
	public function build() {
		$viewpaths = App::path('views');
		$this->AssetBuild->build($viewpaths);
	}

/**
 * Clears the build directories for both CSS and JS
 *
 * @return void
 */
	public function clear() {
		$this->out('Clearing Javascript build files:');
		$this->hr();
		$this->_clearBuilds('js');

		$this->out('');
		$this->out('Clearing CSS build files:');
		$this->hr();
		$this->_clearBuilds('css');
		
		$this->out('Complete');
	}

/**
 * clear the builds for a specific extension.
 *
 * @return void
 */
	protected function _clearBuilds($ext) {
		$targets = $this->_Config->targets($ext);
		if (empty($targets)) {
			$this->err('No ' . $ext . ' build files defined, skipping');
			return;
		}
		if (!$this->_Config->cachingOn($targets[0])) {
			$this->err('Caching not enabled for ' . $ext . ' files, skipping.');
			return;
		}
		$path = $this->_Config->cachePath($ext);
		if (!file_exists($path)) {
			$this->err('Build directory ' . $path . ' for ' . $ext . ' does not exist.');
			return;
		}
		$dir = new DirectoryIterator($path);
		foreach ($dir as $file) {
			$name = $file->getFilename();
			if (in_array($name, array('.', '..'))) {
				continue;
			}
			// no timestamp
			if (in_array($name, $targets)) {
				$this->out('Deleting ' . $path . $name);
				unlink($path . $name);
				continue;
			}
			if (preg_match('/^.*\.v\d+\.[a-z]+$/', $name)) {
				list($base, $v, $ext) = explode('.', $name, 3);
				if (in_array($base . '.' . $ext, $targets)) {
					$this->out('Deleting ' . $path . $name);
					continue;
				}
			}
		}
	}

/**
 * help
 *
 * @return void
 */
	public function help() {
		$this->out('Asset Compress Shell');
		$this->hr();
		$this->out('Usage: cake asset_compress <command>');
		$this->hr();
		$this->out("clear - Clears all existing build files.");
		$this->out("build - Builds compressed files.");
		$this->out();
	}
}
