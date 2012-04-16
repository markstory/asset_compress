<?php
App::uses('AssetProcess', 'AssetCompress.Lib');

/**
 * AssetFilterInterface all filters declared in your config.ini must implement
 * this interface or exceptions will be thrown.
 *
 * @package asset_compress
 */
interface AssetFilterInterface {

/**
 * Input filters are used to do pre-processing on each file in a
 * build target.
 *
 * @param string $filename Name of the file
 * @param string $content Content of the file.
 */
	public function input($filename, $content);

/**
 * Output filters are used to do minification or do other manipulation
 * on the content before $targetFile is saved/output.
 *
 * @param string $target The build target being made.
 * @param string $content The content to filter.
 */
	public function output($targetFile, $content);

/**
 * Gets settings for this filter.  Will always include 'paths'
 * key which points at paths available for the type of asset being generated.
 *
 * @param array $settings Array of settings.
 */
	public function settings($settings);

}


/**
 * A simple base class you can build filters on top of
 * if you only want to implement either input() or output()
 *
 * @package asset_compress
 */
class AssetFilter implements AssetFilterInterface {

/**
 * Settings
 *
 * @var array
 */
	protected $_settings = array();

/**
 * Gets settings for this filter.  Will always include 'paths'
 * key which points at paths available for the type of asset being generated.
 *
 * @param array $settings Array of settings.
 */
	public function settings($settings) {
		$this->_settings = array_merge($this->_settings, $settings);
	}

/**
 * Input filter.
 *
 * @param string $filename Name of the file
 * @param string $content Content of the file.
 */
	public function input($filename, $content) {
		return $content;
	}

/**
 * Output filter.
 *
 * @param string $target The build target being made.
 * @param string $content The content to filter.
 */
	public function output($target, $content) {
		return $content;
	}

/**
 * Run the compressor command and get the output
 *
 * @param string $cmd The command to run.
 * @param string $content The content to run through the command.
 * @return The result of the command.
 * @throws RuntimeException
 */
	protected function _runCmd($cmd, $content, $environment = null) {
		$Process = new AssetProcess();
		$Process->environment($environment);
		$Process->command($cmd)->run($content);

		if ($Process->error()) {
			throw new RuntimeException($Process->error());
		}
		return $Process->output();
	}

/**
 * Find the command executable. If $file is an absolute path
 * to a file that exists $search will not be looked at.
 *
 * @param array $search Paths to search.
 * @param string $file The executable to find.
 */
	protected function _findExecutable($search, $file) {
		$file = str_replace('/', DS, $file);
		if (file_exists($file)) {
			return $file;
		}
		foreach ($search as $path) {
			$path = rtrim($path, DS);
			if (file_exists($path . DS . $file)) {
				return $path . DS . $file;
			}
		}
		return null;
	}
}
