<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * A YUI Compressor adapter for compressing Javascript.
 * This filter assumes you have Java installed on your system and that its accessible
 * via the PATH. It also assumes that the yuicompressor.jar file is located in "vendors/yuicompressor" directory.
 *
 * @package asset_compress.libs.filter
 */
class YuiJs extends AssetFilter {

/**
 * Settings for YuiCompressor based filters.
 *
 * @var array
 */
	protected $_settings = array(
		'path' => 'yuicompressor/yuicompressor.jar'
	);

/**
 * Run $input through YuiCompressor
 *
 * @param string $filename Filename being generated.
 * @param string $input Contents of file
 * @return Compressed file
 */
	public function output($filename, $input) {
		$jar = $this->_findExecutable(App::path('vendors'), $this->_settings['path']);
		$cmd = 'java -jar "' . $jar . '" --type js';
		return $this->_runCmd($cmd, $input);
	}

}
