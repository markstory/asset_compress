<?php
App::import('Lib', 'AssetCompress.filter/YuiBase.php');

/**
 * A YUI Compressor adapter for compressing CSS.
 * This filter assumes you have Java installed on your system and that its accessible
 * via the PATH. It also assumes that the yuicompressor.jar file is located in "vendors/yuicompressor" directory.
 *
 * @package asset_compress.libs.filter
 */
class YuiCss extends YuiBase {

	public function output($filename, $input) {
		$jar = $this->_findExecutable(App::path('vendors'), $this->_settings['path']);
		$cmd = 'java -jar "' . $jar . '" --type css';
		return $this->_run($cmd, $input);
	}

}
