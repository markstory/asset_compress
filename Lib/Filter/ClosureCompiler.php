<?php

App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * Google Closure Compiler API Filter
 *
 * Allows you to filter Javascript files through the Google Closure compiler API. The script
 * needs to have web access to run.
 *
 * @package AssetCompress.Lib.Filter
 */
class ClosureCompiler extends AssetFilter {

/**
 * Defaults.
 *
 * @var array
 */
	protected $_defaults = array('compilation_level' => 'WHITESPACE_ONLY');

/**
 * Settings.
 *
 * NOTE: statistics and warnings are only used when in debug mode.
 *
 * - level (string) Defaults to WHITESPACE_ONLY. Values: SIMPLE_OPTIMIZATIONS, ADVANCED_OPTIMIZATIONS.
 * - statistics (boolean) Defaults to FALSE.
 * - warnings (mixed) Defaults to FALSE. Values: TRUE or QUIET, DEFAULT, VERBOSE.
 *
 * @var array
 */
	protected $_settings = array(
		'level' => null,
		'statistics' => false,
		'warnings' => false
	);

/**
 * Optional API parameters.
 *
 * - The `output_file_name` hasn't been included because AssetCompress is used for saving the minified javascript.
 * - The `warning_level` is automatically handled in `self::$_settings`.
 *
 * @var array
 * @see https://developers.google.com/closure/compiler/docs/api-ref
 */
	private $__params = array(
		'js_externs',
		'externs_url',
		'exclude_default_externs',
		'formatting',
		'use_closure_library',
		'language'
	);

/**
 * {@inheritdoc}
 */
	public function output($filename, $content) {
		$defaults = array('compilation_level' => $this->_settings['level']);

		$errors = $this->_query($content, array('output_info' => 'errors'));
		if (!empty($errors)) {
			throw new Exception(sprintf("%s:\n%s\n", 'Errors', $errors));
		}

		$output = $this->_query($content, array('output_info' => 'compiled_code'));

		if (!Configure::read('debug')) {
			return $output;
		}

		foreach ($this->_settings as $setting => $value) {
			if (!in_array($setting, array('warnings', 'statistics')) || true != $value) {
				continue;
			}

			$args = array('output_info' => $setting);
			if ('warnings' == $setting && in_array($value, array('QUIET', 'DEFAULT', 'VERBOSE'))) {
				$args['warning_level'] = $value;
			}

			$$setting = $this->_query($content, $args);
			printf("%s:\n%s\n", ucfirst($setting), $$setting);
		}

		return $output;
	}

/**
 * Query the Closure compiler API.
 *
 * @param string $content Javascript to compile.
 * @param array $args API parameters.
 * @throws Exception If curl extension is missing.
 * @throws Exception If curl triggers an error.
 * @return string
 */
	protected function _query($content, $args = array()) {
		if (!extension_loaded('curl')) {
			throw new Exception('Missing the `curl` extension.');
		}

		$args = array_merge($this->_defaults, $args);
		if (!empty($this->_settings['level'])) {
			$args['compilation_level'] = $this->_settings['level'];
		}

		foreach ($this->_settings as $key => $val) {
			if (in_array($key, $this->__params)) {
				$args[$key] = $val;
			}
		}

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_URL => 'http://closure-compiler.appspot.com/compile',
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => 'js_code=' . urlencode($content) . '&' . http_build_query($args),
			CURLOPT_RETURNTRANSFER =>  1,
			CURLOPT_HEADER => 0,
			CURLOPT_FOLLOWLOCATION => 0
		));

		$output = curl_exec($ch);

		if (false === $output) {
			throw new Exception('Curl error: ' . curl_error($ch));
		}

		curl_close($ch);
		return $output;
	}
}
