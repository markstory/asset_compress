<?php
App::uses('AssetFilter', 'AssetCompress.Lib');

/**
 * A Google Closure compressor adapter for compressing Javascript.
 * This filter assumes you have Java installed on your system and that its accessible
 * via the PATH. It also assumes that the compiler.jar file is located in "vendors/closure" directory.
 *
 * You can get closure here at http://code.google.com/closure/compiler/
 *
 * @package asset_compress.libs.filter
 */
class ClosureJs extends AssetFilter {

/**
 * Settings for Closure based filters.
 *
 * @var array
 */
	protected $_settings = array(
		'path' => 'closure/compiler.jar',
		'warning_level' => 'QUIET' //Supress warnings by default
	);

/**
 * Run $input through Closure compiler
 *
 * @param string $filename Filename being generated.
 * @param string $input Contents of file
 * @return Compressed file
 */
	public function output($filename, $input) {
		$output = null;
		$jar = $this->_findExecutable(App::path('vendors'), $this->_settings['path']);

		//Closure works better if you specify an input file. Also supress warnings by default
		$tmpFile = tempnam(TMP,'CLOSURE');
		file_put_contents($tmpFile, $input);
		$cmd = 'java -jar "' . $jar . '" --js=' . $tmpFile . ' --warning_level=' . $this->_settings['warning_level'];

		try {
			$output = $this->_runCmd($cmd, null);
		} catch (Exception $e) {
			//If there is an error need to remove tmpFile.
			@unlink($tmpFile);
			throw $e;
		}

		@unlink($tmpFile);
		return $output;
	}

}
