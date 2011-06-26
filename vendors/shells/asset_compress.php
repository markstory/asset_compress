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

		AssetConfig::clearAllCachedKeys();
		
		$this->_Config = AssetConfig::buildFromIniFile($config);
	}

/**
 * Builds all the files defined in the build file.
 *
 * @return void
 */
	public function build() {
		$this->_Config->writeTimestampFile(time());
		$this->build_ini();
		$this->build_dynamic();
	}

	public function build_ini() {
		$this->AssetBuild->setConfig($this->_Config);
		$this->AssetBuild->buildIni();
	}

	public function build_dynamic() {
		$this->AssetBuild->setConfig($this->_Config);
		$viewpaths = App::path('views');
		$this->AssetBuild->buildDynamic($viewpaths);
	}

/**
 * Clears the build directories for both CSS and JS
 *
 * @return void
 */
	public function clear() {
		
		if ($this->_Config->get('General.timestampFile')) {
			$this->clear_build_ts();
		}
		
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
 * Clears out all the cache keys associated with asset_compress.
 * 
 * Note: method really does nothing here cuz keys are cleared in startup.
 * This method exists for times when you just want to clear the cache keys
 * associated with asset_compress
 */	
	public function clear_cache() {
		$this->out('Clearing all cache keys:');
		$this->hr();
	}
	
/**
 * Clears the build timestamp. Try to clear it out even if they do not have ts file enabled in
 * the INI.
 * 
 * build timestamp file is only created when build() is run from this shell
 */	
	public function clear_build_ts() {
		$this->out('Clearing build time stamp:');
		$this->hr();
		AssetConfig::clearBuildTimeStamp();
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
				$this->out(' - Deleting ' . $path . $name);
				unlink($path . $name);
				continue;
			}
			if (preg_match('/^.*\.v\d+\.[a-z]+$/', $name)) {
				list($base, $v, $ext) = explode('.', $name, 3);
				if (in_array($base . '.' . $ext, $targets)) {
					$this->out(' - Deleting ' . $path . $name);
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
		$this->out();
		$this->out('Usage: cake asset_compress <command> <options> <args>');
		$this->out();
		$this->out('Commands:');
		$this->out("clear - Clears all existing build files.");
		$this->out("build - Builds all compressed files.");
		$this->out("build_ini - Build compressed files defined in the ini file.");
		$this->out("build_dynamic - Build compressed files defined in view files.");
		$this->out();
		$this->out('Options:');
		$this->out("config - Choose the config file to use.  Defaults to app/config/asset_compress.ini.");
		$this->out();
	}
}
