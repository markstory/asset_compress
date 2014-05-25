<?php
App::uses('AppHelper', 'View/Helper');
App::uses('AssetScanner', 'AssetCompress.Lib');
App::uses('AssetCache', 'AssetCompress.Lib');
App::uses('AssetConfig', 'AssetCompress.Lib');
App::uses('Hash', 'Utility');
App::uses('Router', 'Routing');

/**
 * AssetCompress Helper.
 *
 * Handle inclusion assets using the AssetCompress features for concatenating and
 * compressing asset files.
 *
 */
class AssetCompressHelper extends AppHelper {

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
 * A list of build files added during the helper runtime.
 *
 * @var array
 */
	protected $_runtime = array(
		'js' => array(),
		'css' => array()
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
			$this->config($config);
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
	public function config($config = null) {
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
			$includeFile = JS . $this->options['autoIncludePath'] . DS . $file;
			if (file_exists($includeFile)) {
				$this->Html->script(
					str_replace(DS, '/', $this->options['autoIncludePath'] . '/' . $file),
					array('inline' => false)
				);
			}
		}
	}

/**
 * Used to include runtime defined build files. To include build files defined in your
 * ini file use script() or css().
 *
 * Calling this method will clear the asset caches.
 *
 * @return string Empty string or string containing asset link tags.
 */
	public function includeAssets($raw = null) {
		if ($raw !== null) {
			$css = $this->includeCss(array('raw' => true));
			$js = $this->includeJs(array('raw' => true));
		} else {
			$css = $this->includeCss();
			$js = $this->includeJs();
		}
		return $css . "\n" . $js;
	}

/**
 * Include the CSS files that were defined at runtime with
 * the helper.
 *
 * ### Usage
 *
 * #### Include one destination file:
 * `$this->AssetCompress->includeCss('default');`
 *
 * #### Include multiple files:
 * `$this->AssetCompress->includeCss('default', 'reset', 'themed');`
 *
 * #### Include all the files:
 * `$this->AssetCompress->includeCss();`
 *
 * @param string $name Name of the destination file to include. You can pass any number of strings in to
 *    include multiple files. Leave null to include all files.
 * @return string A string containing the link tags
 */
	public function includeCss() {
		$args = func_get_args();
		return $this->_genericInclude($args, 'css');
	}

/**
 * Include the Javascript files that were defined at runtime with
 * the helper.
 *
 * ### Usage
 *
 * #### Include one runtime destination file:
 * `$this->AssetCompress->includeJs('default');`
 *
 * #### Include multiple runtime files:
 * `$this->AssetCompress->includeJs('default', 'reset', 'themed');`
 *
 * #### Include all the runtime files:
 * `$this->AssetCompress->includeJs();`
 *
 * @param string $name Name of the destination file to include. You can pass any number of strings in to
 *    include multiple files. Leave null to include all files.
 * @return string A string containing the script tags.
 */
	public function includeJs() {
		$args = func_get_args();
		return $this->_genericInclude($args, 'js');
	}

/**
 * The generic version of includeCss and includeJs
 *
 * @param array $files Array of destination/build files to include
 * @param string $ext The extension builds must have.
 * @return string A string containing asset tags.
 */
	protected function _genericInclude($files, $ext) {
		$numArgs = count($files);
		$options = array();
		if (isset($files[$numArgs - 1]) && is_array($files[$numArgs - 1])) {
			$options = array_pop($files);
			$numArgs -= 1;
		}
		if ($numArgs <= 0) {
			$files = array_keys($this->_runtime[$ext]);
		}
		foreach ($files as &$file) {
			$file = $this->_addExt($file, '.' . $ext);
		}
		$output = array();
		foreach ($files as $build) {
			if (empty($this->_runtime[$ext][$build])) {
				continue;
			}
			if ($ext === 'js') {
				$output[] = $this->script($build, $options);
			} elseif ($ext === 'css') {
				$output[] = $this->css($build, $options);
			}
			unset($this->_runtime[$ext][$build]);
		}
		return implode("\n", $output);
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
		$config = $this->config();
		$buildFiles = $config->files($file);
		if (!$buildFiles) {
			throw new RuntimeException('Cannot create a stylesheet tag for a build that does not exist.');
		}
		$output = '';
		if (!empty($options['raw'])) {
			unset($options['raw']);
			$scanner = new AssetScanner($config->paths('css', $file), $this->theme);
			foreach ($buildFiles as $part) {
				$part = $scanner->find($part, false);
				$part = str_replace(DS, '/', $part);
				$output .= $this->Html->css($part, null, $options);
			}
			return $output;
		}

		$url = $this->url($file, $options);
		unset($options['full']);
		return $this->Html->css($url, null, $options);
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
		$config = $this->config();
		$buildFiles = $config->files($file);
		if (!$buildFiles) {
			throw new RuntimeException('Cannot create a script tag for a build that does not exist.');
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
 * @throws Exception when the build file does not exist.
 */
	public function url($file = null, $full = false) {
		$config = $this->config();
		if (!$config->exists($file)) {
			throw new Exception('Cannot get URL for build file that does not exist.');
		}

		$options = $full;
		if (!is_array($full)) {
			$options = array('full' => $full);
		}
		$options += array('full' => false);
		$type = $config->getExt($file);

		$baseUrl = $config->get($type . '.baseUrl');
		$path = $config->get($type . '.cachePath');
		$devMode = Configure::read('debug') > 0;

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
			$base = method_exists('Router', 'fullBaseUrl') ? Router::fullBaseUrl() : FULL_BASE_URL;
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
		$config = $this->config();
		$ext = $config->getExt($build);
		$hash = $this->_getHashName($build, $ext);
		if ($hash) {
			$build = $hash;
		}
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
		$config = $this->config();
		$ext = $config->getExt($file);
		$query = array();

		if ($config->isThemed($file)) {
			$query['theme'] = $this->theme;
		}

		if (isset($this->_runtime[$ext][$file])) {
			$hash = $this->_getHashName($file, $ext);
			$components = $config->files($file);
			if ($hash) {
				$file = $hash;
			}
			$query['file'] = $components;
		}
		if (substr($base, -1) !== DS && DS !== '\\') {
			$base .= '/';
		}
		$query = empty($query) ? '' : '?' . http_build_query($query);
		return $base . $file . $query;
	}

/**
 * Check if a build file is a magic hash and get the hash name for it.
 *
 * @param string $build The name of the build to check.
 * @param string $ext The extension
 * @return mixed Either false or the string name of the hash.
 */
	protected function _getHashName($build, $ext) {
		if (strpos($build, ':hash') === 0) {
			$buildFiles = $this->config()->files($build);
			return md5(implode('_', $buildFiles)) . '.' . $ext;
		}
		return false;
	}

/**
 * Add a script file to a build target, this lets you define build
 * targets without configuring them in the ini file.
 *
 * @param mixed $files Either a string or an array of files to append into the build target.
 * @param string $target The name of the build target, defaults to a hash of the filenames
 * @return void
 */
	public function addScript($files, $target = ':hash-default.js') {
		$target = $this->_addExt($target, '.js');
		$this->_runtime['js'][$target] = true;
		$config = $this->config();
		$defined = $config->files($target);
		$config->files($target, array_merge($defined, (array)$files));
	}

/**
 * Add a stylesheet file to a build target, this lets you define build
 * targets without configuring them in the ini file.
 *
 * @param mixed $files Either a string or an array of files to append into the build target.
 * @param string $target The name of the build target, defaults to a hash of the filenames
 * @return void
 */
	public function addCss($files, $target = ':hash-default.css') {
		$target = $this->_addExt($target, '.css');
		$this->_runtime['css'][$target] = true;
		$config = $this->config();
		$defined = $config->files($target);
		$config->files($target, array_merge($defined, (array)$files));
	}

/**
 * Check if a build exists (is defined and have at least one file) in the ini file.
 *
 * @param string $file Name of the build that will be checked if exists.
 * @return boolean True if the build file exists.
 */
	public function exists($file) {
		return $this->config()->exists($file);
	}

}
