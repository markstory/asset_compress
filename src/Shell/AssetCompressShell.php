<?php
namespace AssetCompress\Shell;

use AssetCompress\AssetCache;
use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use Cake\Console\Shell;
use Cake\Utility\Folder;
use DirectoryIterator;

/**
 * Asset Compress Shell
 *
 * Assists in clearing and creating the build files this plugin makes.
 *
 */
class AssetCompressShell extends Shell {

	public $tasks = array('AssetCompress.AssetBuild');

	protected $_config;

/**
 * Create the configuration object used in other classes.
 *
 */
	public function startup() {
		parent::startup();

		AssetConfig::clearAllCachedKeys();
		$this->_config = AssetConfig::buildFromIniFile($this->params['config']);
		$this->out();
	}

/**
 * Set the config object.
 *
 * @var \AssetCompress\AssetConfig $config The config instance.
 * @return void
 */
	public function setConfig($config) {
		$this->_config = $config;
		$this->AssetBuild->setConfig($config);
	}

/**
 * Builds all the files defined in the build file.
 *
 * @return void
 */
	public function build() {
		$this->AssetBuild->setConfig($this->_config);
		$this->AssetBuild->build();
	}

/**
 * Clears the build directories for both CSS and JS
 *
 * @return void
 */
	public function clear() {
		$this->clear_build_ts();

		$this->_io->verbose('Clearing Javascript build files:');
		$this->_clearBuilds('js');

		$this->_io->verbose('Clearing CSS build files:');
		$this->_clearBuilds('css');

		$this->_io->verbose('');
		$this->out('<success>Complete</success>');
	}

/**
 * Clears out all the cache keys associated with asset_compress.
 *
 * Note: method really does nothing here because keys are cleared in startup.
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
		$this->_io->verbose('Clearing build timestamp.');
		AssetConfig::clearBuildTimeStamp();
	}

/**
 * clear the builds for a specific extension.
 *
 * @return void
 */
	protected function _clearBuilds($ext) {
		$targets = $this->_config->targets($ext);
		if (empty($targets)) {
			$this->err('No ' . $ext . ' build files defined, skipping');
			return;
		}
		$themes = (array)$this->_config->general('themes');
		$this->_clearPath(CACHE . 'asset_compress' . DS, $themes, $targets);
		$path = $this->_config->cachePath($ext);

		$path = $this->_config->cachePath($ext);
		if (!file_exists($path)) {
			$this->err('Build directory ' . $path . ' for ' . $ext . ' does not exist.');
			return;
		}
		$this->_clearPath($path, $themes, $targets);
	}

/**
 * Clear a path of build targets.
 *
 * @param string $path The path to clear.
 * @param array $themes The themes to clear.
 * @param array $targets The build targets to clear.
 * @return void
 */
	protected function _clearPath($path, $themes, $targets) {
		if (!file_exists($path)) {
			return;
		}
		
		$dir = new DirectoryIterator($path);
		foreach ($dir as $file) {
			$name = $base = $file->getFilename();
			if (in_array($name, array('.', '..'))) {
				continue;
			}
			// timestampped files.
			if (preg_match('/^(.*)\.v\d+(\.[a-z]+)$/', $name, $matches)) {
				$base = $matches[1] . $matches[2];
			}
			// themed files
			foreach ($themes as $theme) {
				if (strpos($base, $theme) === 0) {
					list($themePrefix, $base) = explode('-', $base);
				}
			}
			if (in_array($base, $targets)) {
				$this->_io->verbose(' - Deleting ' . $path . $name);
				unlink($path . $name);
				continue;
			}
		}
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(array(
			'Asset Compress Shell',
			'',
			'Builds and clears assets defined in your asset_compress.ini',
			'file and in your view files.'
		))->addSubcommand('clear', array(
			'help' => 'Clears all builds defined in the ini file.'
		))->addSubcommand('build', array(
			'help' => 'Generate all builds defined in the ini files.'
		))->addOption('config', array(
			'help' => 'Choose the config file to use.',
			'short' => 'c',
			'default' => CONFIG . 'asset_compress.ini'
		))->addOption('force', array(
			'help' => 'Force assets to rebuild. Ignores timestamp rules.',
			'short' => 'f',
			'boolean' => true
		));
	}

}
