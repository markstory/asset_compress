<?php
App::import('Model', 'AssetCompress.AssetFilterInterface');

/**
 * A YUI Compressor adapter for compressing CSS.
 * This filter assumes you have Java installed on your system and that its accessible
 * via the PATH. It also assumes that the yuicompressor.jar file is located in "vendors/yuicompressor" directory.
 *
 * @package asset_compress.libs.filter
 */
class YuiCssFilter implements AssetFilterInterface {

	public function filter($input) {
		$output = '';
		$JAR_PATH = $this->_find(App::path('vendors'), 'yuicompressor' . DS . 'yuicompressor.jar');
		$cmd = 'java -jar "' . $JAR_PATH . 'yuicompressor' . DS . 'yuicompressor.jar" --type css';

		$descriptor_spec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w')
		);
		$process = proc_open($cmd, $descriptor_spec, $pipes);

		if (is_resource($process)) {
			fwrite($pipes[0], $input);
			fclose($pipes[0]);

			$output = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($process);
		}
		return $output;
	}

	protected function _find($search, $file) {
		foreach ($search as $path) {
			$path = rtrim($path, DS);
			if (file_exists($path . DS . $file)) {
				return $path . DS;
			}
		}
		return null;
	} 
}