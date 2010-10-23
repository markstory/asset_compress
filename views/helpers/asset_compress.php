<?php
/**
 * AssetCompress Helper.
 *
 * Handle inclusion assets using the AssetCompress features for concatenating and
 * compressing asset files.
 *
 * You add files to be compressed using `script` and `css`.  All files added to a key name
 * will be processed and joined before being served.  When in debug = 2, no files are cached.
 *
 * If debug = 0, the processed file will be cached to disk.  You can also use the routes
 * and config file to create static 'built' files. These built files must have unique names, or
 * as they are made they will overwrite each other.  You can clear built files
 * with the shell provided in the plugin.
 *
 * @package asset_compress.helpers
 * @author Mark Story
 */
class AssetCompressHelper extends AppHelper {

	public $helpers = array('Html');

/**
 * Options for the helper
 *
 * - `autoIncludePath` - Path inside of webroot/js that contains autoloaded view js.
 * - `jsCompressUrl` - Url to use for getting compressed js files.
 * - `cssCompressUrl` - Url to use for getting compressed css files.
 *
 * @var array
 */
	public $options = array(
		'autoIncludePath' => 'views',
		'cssCompressUrl' => array(
			'plugin' => 'asset_compress',
			'controller' => 'css_files',
			'action' => 'get',
			'ext' => 'css'
		),
		'jsCompressUrl' => array(
			'plugin' => 'asset_compress',
			'controller' => 'js_files',
			'action' => 'get',
			'ext' => 'js'
		)
	);

/**
 * Head scripts to be included keyed by final filename.
 *
 * @var array
 */
	protected $_topScripts = array();

/**
 * Bottom scripts to be included keyed by final filename.
 *
 * @var array
 */
	protected $_bottomScripts = array();

/**
 * CSS files to be included keyed by final filename.
 *
 * @var array
 */
	protected $_css = array();

/**
 * Disable autoInclusion of view js files.
 *
 * @var string
 */
	public $autoInclude = true;

/**
 * parsed ini file values.
 *
 * @var array
 */
	protected $_iniFile;

/**
 * Contains the build timestamp from the file.
 *
 * @var string
 */
	protected $_buildTimestamp;

/**
 * Constructor - finds and parses the ini file the plugin uses.
 *
 * @return void
 */
	public function __construct($options = array()) {
		if (!empty($options['iniFile'])) {
			$iniFile = $options['iniFile'];
		} else {
			$iniFile = CONFIGS . 'asset_compress.ini';
		}
		if (!file_exists($iniFile)) {
			$iniFile = App::pluginPath('AssetCompress') . 'config' . DS . 'config.ini';
		}
		$this->_iniFile = parse_ini_file($iniFile, true);
	}

/**
 * Set options, merge with existing options.
 *
 * @return void
 */
	public function options($options) {
		$this->options = Set::merge($options);
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
	public function afterRender() {
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
				$this->Html->script($this->options['autoIncludePath'] . '/' . $file, array('inline' => false));
			}
		}
	}

/**
 * Includes top css + js assets.  If debug = 0 check the config settings and either look for a premade cache
 * file or use requestAction.  When file caching is enabled the first requestAction will create the cache
 * file used for all subsequent requests.
 *
 * Calling this method will clear the top asset caches.
 *
 * @param boolean $inline Whether you want the files inline or added to scripts_for_layout
 * @return string Empty string or string containing asset link tags.
 */
	public function includeTopAssets($inline = true) {
		$css = $this->includeCss();
		$js = $this->includeTopJs();
		return $css . "\n" . $js;
	}

/**
 * Includes bottom js assets.  If debug = 0 check the config settings and either look for a premade cache
 * file or use requestAction.  When file caching is enabled the first requestAction will create the cache
 * file used for all subsequent requests.
 *
 * Calling this method will clear the bottom asset caches.
 *
 * @param boolean $inline Whether you want the files inline or added to scripts_for_layout
 * @return string Empty string or string containing asset link tags.
 */	
	public function includeBottomAssets($inline = true) {
		$js = $this->includeBottomJs();
		return $js;
	}

/**
* Alias for includeTopAssets()
*/
	public function includeAssets($inline = true) {
		return $this->includeTopAssets($inline);
	}

/**
 * Include the CSS files 
 *
 * ### Usage
 *
 * #### Include one destination file:
 * `$assetCompress->includeCss('default');`
 *
 * #### Include multiple files:
 * `$assetCompress->includeCss('default', 'reset', 'themed');`
 *
 * #### Include all the files:
 * `$assetCompress->includeCss();`
 *
 * @param string $name Name of the destination file to include.  You can pass any number of strings in to
 *    include multiple files.  Leave null to include all files.
 * @return string A string containing the link tags
 */
	public function includeCss() {
		$files = func_get_args();
		return $this->_genericInclude($files, '_css', 'cssCompressUrl');
	}

/**
 * Include the Javascript files to the top
 *
 * ### Usage
 *
 * #### Include one destination file:
 * `$assetCompress->includeTopJs('default');`
 *
 * #### Include multiple files:
 * `$assetCompress->includeTopJs('default', 'reset', 'themed');`
 *
 * #### Include all the files:
 * `$assetCompress->includeTopJs();`
 *
 * @param string $name Name of the destination file to include.  You can pass any number of strings in to
 *    include multiple files.  Leave null to include all files.
 * @return string A string containing the script tags.
 */ 
	public function includeTopJs() {
		$files = func_get_args();
		return $this->_genericInclude($files, '_topScripts', 'jsCompressUrl');
	}

/**
 * Include the Javascript files to the bottom
 *
 * ### Usage
 *
 * #### Include one destination file:
 * `$assetCompress->includeBottomJs('default');`
 *
 * #### Include multiple files:
 * `$assetCompress->includeBottomJs('default', 'reset', 'themed');`
 *
 * #### Include all the files:
 * `$assetCompress->includeBottomJs();`
 *
 * @param string $name Name of the destination file to include.  You can pass any number of strings in to
 *    include multiple files.  Leave null to include all files.
 * @return string A string containing the script tags.
 */ 	
	public function includeBottomJs() {
		$files = func_get_args();
		return $this->_genericInclude($files, '_bottomScripts', 'jsCompressUrl');
	}
	
/**
 * The generic version of includeCss and includeJs
 *
 * @param array $files Array of destination/build files to include
 * @param string $property The property to use
 * @param string $urlKey The key that contains the url for the build files.
 * @return string A string containing asset tags.
 */
	protected function _genericInclude($files, $property, $urlKey) {
		if (count($files) == 0) {
			$files = array_keys($this->{$property});
		}
		$output = array();
		foreach ($files as $destination) {
			if (empty($this->{$property}[$destination])) {
				continue;
			}
			$output[] = $this->_generateAsset(
				$property, $destination, $this->{$property}[$destination], $this->options[$urlKey]
			);
			unset($this->{$property}[$destination]);
		}
		return implode("\n", $output);
	}

/**
 * Generates the asset tag of the chosen $method
 *
 * @param string $method Method name to call on HtmlHelper
 * @param string $destination The destination file to be generated.
 * @param array $url Array of url keys for making the asset location.
 * @return string Asset tag.
 */
	protected function _generateAsset($method, $destination, $files, $url) {
		$fileString = 'file[]=' . implode('&amp;file[]=', $files);
		$iniKey = $method == '_css' ? 'Css' : 'Javascript';

		if (!empty($this->_iniFile[$iniKey]['timestamp']) && Configure::read('debug') < 2) {
			$destination = $this->_timestampFile($destination);
		}

		//escape out of prefixes.
		$prefixes = Router::prefixes();
		foreach ($prefixes as $prefix) {
			if (!array_key_exists($prefix, $url)) {
				$url[$prefix] = false;
			}
		}

		$url = Router::url(array_merge(
			$url,
			array($destination, '?' => $fileString, 'base' => false)
		));

		list($base, $query) = explode('?', $url);
		if (file_exists(WWW_ROOT . $base)) {
			$url = $base;
		}
		if ($method == '_css') {
			return $this->Html->css($url);
		} else {			
			return $this->Html->script($url);
		}
	}

/**
 * Adds the build timestamp to a filename
 *
 * @return void
 */
	protected function _timestampFile($name) {
		if (empty($this->_buildTimestamp) && file_exists(TMP . 'asset_compress_build_time')) {
			$this->_buildTimestamp = '.' . file_get_contents(TMP . 'asset_compress_build_time');
		} elseif (empty($this->_buildTimestamp)) {
			$this->_buildTimestamp = '.' . time();
		}
		return $name . $this->_buildTimestamp;
	}

/**
 * Include a Javascript file.  All files with the same `$destination` will be compressed into one file.
 * Compression/concatenation will only occur if debug == 0.
 *
 * @param mixed $file Either a string filename or an array of filenames to include.
 * @param string $destination Name of file that $file should be compacted into.
 * @return void
 */
	public function script($property = '_topScripts', $file, $destination = 'default') {
		if (empty($this->{$property}[$destination])) {
			$this->{$property}[$destination] = array();
		}
		$this->{$property}[$destination] = array_merge($this->{$property}[$destination], (array)$file);
	}

/**
 * Include a Header Javascript file.  All files with the same `$destination` will be compressed into one file.
 * Compression/concatenation will only occur if debug == 0.
 *
 * @param mixed $file Either a string filename or an array of filenames to include.
 * @param string $destination Name of file that $file should be compacted into, if $destination == 'default' the filename will be a concatenation of js filenames or a md5 hash
 * @return void
 */
	public function topScript($file, $destination = 'default') {
		$this->script('_topScripts', $file, $destination);
	}

/**
 * Include a Bottom Javascript file.  All files with the same `$destination` will be compressed into one file.
 * Compression/concatenation will only occur if debug == 0.
 *
 * @param mixed $file Either a string filename or an array of filenames to include.
 * @param string $destination Name of file that $file should be compacted into, if $destination == 'default' the filename will be a concatenation of js filenames or a md5 hash
 * @return void
 */	
	public function bottomScript($file, $destination = 'default') {
		$this->script('_bottomScripts', $file, $destination);
	}

/**
 * Include a CSS file.  All files with the same `$destination` will be compressed into one file.
 * Compression/concatenation will only occur if debug == 0.
 *
 * @param mixed $file Either a string filename or an array of filenames to include.
 * @param string $destination Name of file that $file should be compacted into.
 * @return void
 */
	public function css($file, $destination = 'default') {
		if (empty($this->_css[$destination])) {
			$this->_css[$destination] = array();
		}
		$this->_css[$destination] = array_merge($this->_css[$destination], (array)$file);
	}
}