<?php
namespace AssetCompress\View\Helper;

use AssetCompress\AssetCache;
use AssetCompress\AssetCompiler;
use AssetCompress\AssetConfig;
use AssetCompress\AssetScanner;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Cake\View\View;
use RuntimeException;

/**
 * AssetCompress Helper.
 *
 * Handle inclusion assets using the AssetCompress features for concatenating and
 * compressing asset files.
 *
 */
class AssetCompressHelper extends Helper {

/**
 * Helpers used.
 *
 * @var array
 */
	public $helpers = array('Html');

/**
 * Configuration object
 *
 * @var AssetConfig
 */
	protected $_Config;

/**
 * Cacher object
 *
 * @var AssetCache
 */
	protected $_AssetCache;

/**
 * Options for the helper
 *
 * - `autoIncludePath` - Path inside of webroot/js that contains autoloaded view js.
 *
 * @var array
 */
	public $options = array(
		'autoIncludePath' => 'views'
	);

/**
 * Disable autoInclusion of view js files.
 *
 * @var string
 */
	public $autoInclude = true;

/**
 * Constructor - finds and parses the ini file the plugin uses.
 *
 * @return void
 */
	public function __construct(View $View, $settings = array()) {
		if (empty($settings['noconfig'])) {
			$config = AssetConfig::buildFromIniFile();
			$this->assetConfig($config);
		}
		parent::__construct($View, $settings);
	}

/**
 * Modify the runtime configuration of the helper.
 * Used as a get/set for the ini file values.
 *
 * @param string $name The dot separated config value to change ie. Css.searchPaths
 * @param mixed $value The value to set the config to.
 * @return mixed Either the value being read or null. Null also is returned when reading things that don't exist.
 */
	public function assetConfig($config = null) {
		if ($config === null) {
			return $this->_Config;
		}
		$this->_Config = $config;
		$this->_AssetCache = new AssetCache($config);
	}

/**
 * Accessor for the cache object, useful for testing.
 *
 * @return AssetCache
 */
	public function cache() {
		return $this->_AssetCache;
	}

/**
 * Set options, merge with existing options.
 *
 * @return void
 */
	public function options($options) {
		$this->options = Hash::merge($this->options, $options);
	}

/**
 * AfterRender callback.
 *
 * Adds automatic view js files if enabled.
 * Adds css/js files that have been added to the concatenation lists.
 *
 * Auto file inclusion adopted from Graham Weldon's helper
 * http://bakery.cakephp.org/articles/view/automatic-javascript-includer-helper
 *
 * @return void
 */
	public function afterRender($viewFile) {
		$this->_includeViewJs();
	}

/**
 * Includes the auto view js files if enabled.
 *
 * @return void
 */
	protected function _includeViewJs() {
		if (!$this->autoInclude) {
			return;
		}
		$files = array(
			$this->params['controller'] . '.js',
			$this->params['controller'] . DS . $this->params['action'] . '.js'
		);

		foreach ($files as $file) {
			$includeFile = Configure::read('App.jsBaseUrl') . $this->options['autoIncludePath'] . DS . $file;
			if (file_exists($includeFile)) {
				$this->Html->script(
					str_replace(DS, '/', $this->options['autoIncludePath'] . '/' . $file),
					array('inline' => false)
				);
			}
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

/**
 * Create a CSS file. Will generate link tags
 * for either the dynamic build controller, or the generated file if it exists.
 *
 * To create build files without configuration use addCss()
 *
 * Options:
 *
 * - All options supported by HtmlHelper::css() are supported.
 * - `raw` - Set to true to get one link element for each file in the build.
 *
 * @param string $file A build target to include.
 * @param array $options An array of options for the stylesheet tag.
 * @throws RuntimeException
 * @return A stylesheet tag
 */
	public function css($file, $options = array()) {
		$file = $this->_addExt($file, '.css');
		$config = $this->assetConfig();
		$buildFiles = $config->files($file);
		if (!$buildFiles) {
			throw new RuntimeException(
				"Cannot create a stylesheet tag for a '$file'. That build is not defined."
			);
		}
		$output = '';
		if (!empty($options['raw'])) {
			unset($options['raw']);
			$scanner = new AssetScanner($config->paths('css', $file), $this->theme);
			foreach ($buildFiles as $part) {
				$part = $scanner->find($part, false);
				$part = str_replace(DS, '/', $part);
				$output .= $this->Html->css($part, $options);
			}
			return $output;
		}

		$url = $this->url($file, $options);
		unset($options['full']);
		return $this->Html->css($url, $options);
	}

/**
 * Create a script tag for a script asset. Will generate script tags
 * for either the dynamic build controller, or the generated file if it exists.
 *
 * To create build files without configuration use addScript()
 *
 * Options:
 *
 * - All options supported by HtmlHelper::css() are supported.
 * - `raw` - Set to true to get one script element for each file in the build.
 *
 * @param string $file A build target to include.
 * @param array $options An array of options for the script tag.
 * @throws RuntimeException
 * @return A script tag
 */
	public function script($file, $options = array()) {
		$file = $this->_addExt($file, '.js');
		$config = $this->assetConfig();
		$buildFiles = $config->files($file);
		if (!$buildFiles) {
			throw new RuntimeException(
				"Cannot create a script tag for a '$file'. That build is not defined."
			);
		}
		if (!empty($options['raw'])) {
			$output = '';
			unset($options['raw']);
			$scanner = new AssetScanner($config->paths('js', $file), $this->theme);
			foreach ($buildFiles as $part) {
				$part = $scanner->find($part, false);
				$part = str_replace(DS, '/', $part);
				$output .= $this->Html->script($part, $options);
			}
			return $output;
		}

		$url = $this->url($file, $options);
		unset($options['full']);

		return $this->Html->script($url, $options);
	}

/**
 * Get the URL for a given asset name.
 *
 * Takes an build filename, and returns the URL
 * to that build file.
 *
 * @param string $file The build file that you want a URL for.
 * @param array $options Options for URL generation.
 * @return string The generated URL.
 * @throws RuntimeException when the build file does not exist.
 */
	public function url($file = null, $full = false) {
		$config = $this->assetConfig();
		if (!$config->exists($file)) {
			throw new RuntimeException('Cannot get URL for build file that does not exist.');
		}

		$options = $full;
		if (!is_array($full)) {
			$options = array('full' => $full);
		}
		$options += array('full' => false);
		$type = $config->getExt($file);

		$baseUrl = $config->get($type . '.baseUrl');
		$path = $config->get($type . '.cachePath');
		$devMode = Configure::read('debug');

		// CDN routes.
		if ($baseUrl && !$devMode) {
			return $baseUrl . $this->_getBuildName($file);
		}

		if (!$devMode) {
			$path = str_replace(WWW_ROOT, '/', $path);
			$path = rtrim($path, '/') . '/';
			$route = $path . $this->_getBuildName($file);
		}
		if ($devMode || $config->general('alwaysEnableController')) {
			$baseUrl = str_replace(WWW_ROOT, '/', $path);
			$route = $this->_getRoute($file, $baseUrl);
		}

		if (DS === '\\') {
			$route = str_replace(DS, '/', $route);
		}

		if ($options['full']) {
			$base = Router::fullBaseUrl();
			return $base . $route;
		}

		return $route;
	}

/**
 * Get the build file name.
 *
 * Generates filenames that are intended for production use
 * with statically generated files.
 *
 * @param string $build The build being resolved.
 * @return string The resolved build name.
 */
	protected function _getBuildName($build) {
		$config = $this->assetConfig();
		$ext = $config->getExt($build);
		$config->theme($this->theme);
		return $this->_AssetCache->buildFileName($build);
	}

/**
 * Get the dynamic build path for an asset.
 *
 * This generates URLs that work with the development dispatcher filter.
 *
 * @param string $file The build file you want to make a url for.
 * @param string $base The base path to fetch a url with.
 * @return string Generated URL.
 */
	protected function _getRoute($file, $base) {
		$config = $this->assetConfig();
		$ext = $config->getExt($file);
		$query = array();

		if ($config->isThemed($file)) {
			$query['theme'] = $this->theme;
		}

		if (substr($base, -1) !== DS && DS !== '\\') {
			$base .= '/';
		}
		$query = empty($query) ? '' : '?' . http_build_query($query);
		return $base . $file . $query;
	}

/**
 * Check if a build exists (is defined and have at least one file) in the ini file.
 *
 * @param string $file Name of the build that will be checked if exists.
 * @return boolean True if the build file exists.
 */
	public function exists($file) {
		return $this->assetConfig()->exists($file);
	}

/**
 * Create a CSS file. Will generate inline style tags
 * in production, or reference the dynamic build file in development
 *
 * To create build files without configuration use addCss()
 *
 * Options:
 *
 * - All options supported by HtmlHelper::css() are supported.
 *
 * @param string $file A build target to include.
 * @throws RuntimeException
 * @return string style tag
 */
	public function inlineCss($file) {
		$config = $this->assetConfig();
		$buildFiles = $config->files($file);
		if (!$buildFiles) {
			throw new RuntimeException('Cannot create a stylesheet for a build that does not exist.');
		}

		$compiler = new AssetCompiler($config);
		$results = $compiler->generate($file);

		return $this->Html->tag('style', $results, array('type' => 'text/css'));
	}

/**
 * Create an inline script tag for a script asset. Will generate inline script tags
 * in production, or reference the dynamic build file in development.
 *
 * To create build files without configuration use addScript()
 *
 * Options:
 *
 * - All options supported by HtmlHelper::css() are supported.
 *
 * @param string $file A build target to include.
 * @throws RuntimeException
 * @return string script tag
 */
	public function inlineScript($file) {
		$config = $this->assetConfig();
		$buildFiles = $config->files($file);
		if (!$buildFiles) {
			throw new RuntimeException('Cannot create a script tag for a build that does not exist.');
		}

		$compiler = new AssetCompiler($config);
		$results = $compiler->generate($file);

		return $this->Html->tag('script', $results);
	}

}
