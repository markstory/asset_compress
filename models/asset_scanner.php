<?php
/**
 * Scan a set of paths for files with the correct criteria.
 *
 * @package asset_compress
 */
class AssetScanner {

/**
 * Paths this scanner should scan.
 *
 * @var array
 */
	protected $_paths = array();

	public function __construct($paths) {
		$this->_paths = $paths;
	}

/**
 * Find a file in the connected paths, and read its contents. 
 *
 * @param string $file The file you want to find.
 * @return mixed Either false on a miss, or the contents of the file.
 */
	public function find($file) {
	
	}
}
