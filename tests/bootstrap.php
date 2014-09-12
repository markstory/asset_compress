<?php
/**
 * Test suite bootstrap.
 *
 * @copyright     Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\I18n;

require_once 'vendor/autoload.php';

// Path constants to a few helpful things.
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__) . DS);
define('CAKE_CORE_INCLUDE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', ROOT . 'vendor' . DS . 'cakephp' . DS . 'cakephp' . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('TESTS', ROOT . 'tests');
define('APP', ROOT . 'tests' . DS . 'test_files' . DS);
define('APP_DIR', 'test_files');
define('WEBROOT_DIR', 'webroot');
define('WWW_ROOT', ROOT . 'webroot' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', APP . 'config' . DS);
define('CACHE', TMP);
define('LOGS', TMP);

require_once CORE_PATH . 'config/bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

Configure::write('debug', true);
Configure::write('App', [
	'namespace' => 'App',
	'encoding' => 'UTF-8',
	'base' => false,
	'baseUrl' => false,
	'dir' => 'src',
	'webroot' => 'webroot',
	'www_root' => APP . 'webroot',
	'fullBaseUrl' => 'http://localhost',
	'imageBaseUrl' => 'img/',
	'jsBaseUrl' => 'js/',
	'cssBaseUrl' => 'css/',
	'paths' => [
		'plugins' => [APP . 'Plugin' . DS],
		'templates' => [APP . 'Template' . DS]
	]
]);

Cache::config([
	'_cake_core_' => [
		'engine' => 'File',
		'prefix' => 'cake_core_',
		'serialize' => true
	],
	'_cake_model_' => [
		'engine' => 'File',
		'prefix' => 'cake_model_',
		'serialize' => true
	],
	'default' => [
		'engine' => 'File',
		'prefix' => 'default_',
		'serialize' => true
	],
	'asset_compress' => [
		'engine' => 'File',
		'prefix' => 'asset_compress_',
		'serialize' => true
	]
]);

Plugin::load('AssetCompress', ['path' => ROOT]);
