<?php
namespace AssetCompress\View\Helper;

use AssetCompress\AssetConfig;
use AssetCompress\AssetTarget;
use AssetCompress\Factory;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
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
class AssetCompressHelper extends Helper
{

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
     * Factory for other AssetCompress objects.
     *
     * @var AssetCompress\Factory
     */
    protected $factory;

    /**
     * AssetCollection for the current config set.
     *
     * @var AssetCompress\AssetCollection
     */
    protected $collection;

    /**
     * AssetWriter instance
     *
     * @var AssetCompress\AssetWriter
     */
    protected $writer;

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
    public function __construct(View $View, $settings = array())
    {
        parent::__construct($View, $settings);
        if (empty($settings['noconfig'])) {
            $config = AssetConfig::buildFromIniFile();
            $this->assetConfig($config);
        }
    }

    /**
     * Modify the runtime configuration of the helper.
     * Used as a get/set for the ini file values.
     *
     * @param AssetCompress\AssetConfig $config The config instance to set.
     * @return Either the current config object or null.
     */
    public function assetConfig($config = null)
    {
        if ($config === null) {
            return $this->_Config;
        }
        $this->_Config = $config;
    }

    /**
     * Get the AssetCompress factory based on the config object.
     *
     * @return AssetCompress\Factory
     */
    protected function factory()
    {
        if (empty($this->factory)) {
            $this->_Config->theme($this->theme);
            $this->factory = new Factory($this->_Config);
        }
        return $this->factory;
    }

    /**
     * Get the AssetCollection
     *
     * @return AssetCompress\AssetCollection
     */
    protected function collection()
    {
        if (empty($this->collection)) {
            $this->collection = $this->factory()->assetCollection();
        }
        return $this->collection;
    }

    /**
     * Get the AssetWriter
     *
     * @return AssetCompress\AssetWriter
     */
    protected function writer()
    {
        if (empty($this->writer)) {
            $this->writer = $this->factory()->writer();
        }
        return $this->writer;
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
    public function afterRender($viewFile)
    {
        $this->_includeViewJs();
    }

    /**
     * Includes the auto view js files if enabled.
     *
     * @return void
     */
    protected function _includeViewJs()
    {
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
    protected function _addExt($file, $ext)
    {
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
    public function css($file, $options = array())
    {
        $file = $this->_addExt($file, '.css');
        if (!$this->collection()->contains($file)) {
            throw new RuntimeException(
                "Cannot create a stylesheet tag for a '$file'. That build is not defined."
            );
        }
        $output = '';
        if (!empty($options['raw'])) {
            unset($options['raw']);
            $target = $this->collection()->get($file);
            foreach ($target->files() as $part) {
                $path = $this->_relativizePath($part->path());
                $output .= $this->Html->css($path, $options);
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
    public function script($file, $options = array())
    {
        $file = $this->_addExt($file, '.js');
        if (!$this->collection()->contains($file)) {
            throw new RuntimeException(
                "Cannot create a script tag for a '$file'. That build is not defined."
            );
        }
        $output = '';
        if (!empty($options['raw'])) {
            unset($options['raw']);
            $target = $this->collection()->get($file);
            foreach ($target->files() as $part) {
                $path = $this->_relativizePath($part->path());
                $output .= $this->Html->script($path, $options);
            }
            return $output;
        }

        $url = $this->url($file, $options);
        unset($options['full']);

        return $this->Html->script($url, $options);
    }

    /**
     * Converts an absolute path into a web relative one.
     *
     * @param string $path The path to convert
     * @return string A webroot relative string.
     */
    protected function _relativizePath($path)
    {
        $plugins = Plugin::loaded();
        $index = array_search('AssetCompress', $plugins);
        unset($plugins[$index]);

        if ($plugins) {
            $pattern = '/(' . implode('|', $plugins) . ')/';
            if (preg_match($pattern, $path, $matches)) {
                $pluginPath = Plugin::path($matches[1]) . 'webroot';
                return str_replace($pluginPath, '/' . Inflector::underscore($matches[1]), $path);
            }
        }
        $path = str_replace(WWW_ROOT, '/', $path);
        return str_replace(DS, '/', $path);
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
    public function url($file = null, $full = false)
    {
        $collection = $this->collection();
        if (!$collection->contains($file)) {
            throw new RuntimeException('Cannot get URL for build file that does not exist.');
        }

        $options = $full;
        if (!is_array($full)) {
            $options = array('full' => $full);
        }
        $options += array('full' => false);

        $target = $collection->get($file);
        $type = $target->ext();

        $config = $this->assetConfig();
        $baseUrl = $config->get($type . '.baseUrl');
        $devMode = Configure::read('debug');


        // CDN routes.
        if ($baseUrl && !$devMode) {
            return $baseUrl . $this->_getBuildName($target);
        }

        $path = $target->outputDir();
        $path = str_replace(WWW_ROOT, '/', $path);
        if (!$devMode) {
            $path = rtrim($path, '/') . '/';
            $route = $path . $this->_getBuildName($target);
        }
        if ($devMode || $config->general('alwaysEnableController')) {
            $route = $this->_getRoute($target, $path);
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
     * @param AssetCompress\AssetTarget $build The build being resolved.
     * @return string The resolved build name.
     */
    protected function _getBuildName(AssetTarget $build)
    {
        return $this->writer()->buildFileName($build);
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
    protected function _getRoute(AssetTarget $file, $base)
    {
        $query = array();

        if ($file->isThemed()) {
            $query['theme'] = $this->theme;
        }

        if (substr($base, -1) !== DS && DS !== '\\') {
            $base .= '/';
        }
        $query = empty($query) ? '' : '?' . http_build_query($query);
        return $base . $file->name() . $query;
    }

    /**
     * Check if a build exists (is defined and have at least one file) in the ini file.
     *
     * @param string $file Name of the build that will be checked if exists.
     * @return boolean True if the build file exists.
     */
    public function exists($file)
    {
        return $this->collection()->contains($file);
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
    public function inlineCss($file)
    {
        $collection = $this->collection();
        if (!$collection->contains($file)) {
            throw new RuntimeException('Cannot create a stylesheet for a build that does not exist.');
        }
        $compiler = $this->factory()->compiler();
        $results = $compiler->generate($collection->get($file));

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
    public function inlineScript($file)
    {
        $collection = $this->collection();
        if (!$collection->contains($file)) {
            throw new RuntimeException('Cannot create a script tag for a build that does not exist.');
        }
        $compiler = $this->factory()->compiler();
        $results = $compiler->generate($collection->get($file));
        return $this->Html->tag('script', $results);
    }
}
