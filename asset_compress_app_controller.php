<?php
/**
 * Asset Compress base controller.  Directly extends Controller to fix issues
 * with authentication issues and component callbacks in the AppController causing
 * CSS and JS files to not correctly be created.
 *
 * @package asset_compress
 * @author Mark Story
 */
class AssetCompressAppController extends Controller {
	public $components = false;
}
