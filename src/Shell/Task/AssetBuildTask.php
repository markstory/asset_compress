<?php
namespace AssetCompress\Shell\Task;

use App\Console\Command\AppShell;
use AssetCompress\AssetCache;
use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use Cake\Console\Shell;
use Cake\Utility\Folder;

class AssetBuildTask extends Shell {

	protected $_config;

	protected $_themes = array();

	protected $_files = array();

/**
 * Set the Configuration object that will be used.
 *
 * @param \AssetCompress\AssetConfig $config The config object.
 * @return void
 */
	public function setConfig(AssetConfig $Config) {
		$this->_config = $Config;
		$this->Compiler = new AssetCompiler($this->_config);
		$this->Cacher = new AssetCache($this->_config);
	}

/**
 * Set the themes to scan.
 *
 * @param array
 * @return void
 */
	public function setThemes($themes) {
		$this->_themes = (array)$themes;
	}

/**
 * Build all the files declared in the Configuration object.
 *
 * @return void
 */
	public function buildIni() {
		$targets = $this->_config->targets('js');
		foreach ($targets as $t) {
			$this->_buildTarget($t);
		}
		$targets = $this->_config->targets('css');
		foreach ($targets as $t) {
			$this->_buildTarget($t);
		}
	}

/**
 * Generate and save the cached file for a build target.
 *
 * @param string $build The build to generate.
 * @return void
 */
	protected function _buildTarget($build) {
		if ($this->_config->isThemed($build)) {
			foreach ($this->_themes as $theme) {
				$this->_config->theme($theme);
				$this->_generateFile($build);
			}
		} else {
			$this->_generateFile($build);
		}
	}

/**
 * Generate a build file.
 *
 * @param string $build The build name to generate.
 * @return void
 */
	protected function _generateFile($build) {
		$name = $this->Cacher->buildFileName($build);
		if ($this->Cacher->isFresh($build) && empty($this->params['force'])) {
			$this->out('<info>Skip building</info> ' . $name . ' existing file is still fresh.');
			return;
		}
		
		$this->Cacher->invalidate($build);

		$name = $this->Cacher->buildFileName($build);
		try {
			$this->out('<success>Saving file</success> for ' . $name);
			$contents = $this->Compiler->generate($build);
			$this->Cacher->write($build, $contents);
		} catch (Exception $e) {
			$this->err('Error: ' . $e->getMessage());
		}
	}

}
