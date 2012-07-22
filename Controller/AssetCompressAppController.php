<?php
App::uses('AppController', 'Controller');

/**
 * Asset Compress base controller. Stubs out some of the controller processes so
 * components in AppController don't interfere with the generation of asset files.
 *
 * @package asset_compress
 * @author Mark Story
 */
class AssetCompressAppController extends AppController {

/**
 * Stub off the startupProcess so components don't mess around with asset compression
 *
 * @return void
 */
	public function startupProcess() {
		$this->beforeFilter();
	}

/**
 * Stub off the shutDown so components don't mess around with asset compression
 *
 * @return void
 */
	public function shutdownProcess() {
	}

}
