<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');

/**
 * Base class for YUICompressor filters.
 *
 */
abstract class YuiBase extends AssetFilter {

	protected $_settings = array(
		'path' => 'yuicompressor/yuicompressor.jar'
	);

/**
 * Run the compressor command and get the output
 *
 * @param string $cmd The command to run.
 * @param string $content The content to run through the command.
 * @return The result of the command.
 */
	protected function _run($cmd, $content) {
		$output = '';
		$descriptor_spec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w')
		);
		$process = proc_open($cmd, $descriptor_spec, $pipes);

		if (is_resource($process)) {
			fwrite($pipes[0], $content);
			fclose($pipes[0]);

			$output = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($process);
		}
		return $output;
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
