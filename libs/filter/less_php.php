<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
#App::import('Lib', 'AssetCompress.ImportInline', array('file' => 'filter' . DS . 'import_inline.php'));
/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * @see http://leafo.net/lessphp/
 */
class LessPhp extends AssetFilter {
	
	/*
	
	setting these in the ini file did not work for me I had to change these to my paths to even get close
	less does work from comment line but not using this filter node outputs an error
	
	*/

	protected $_settings = array(
		'ext' => '.less',
		'path' => 'lessphp/lessc',
		'file' => 'lessphp/lessc.inc.php'
	);
    
    /**
    * Supported extensions for this processor.
    *
    * @var array
    */
    protected $_extensions = array('.less', '.less.css');
    
    public function settings($settings) {
	parent::settings($settings);
    }

/**
 * Apply all the input filters in sequence to the file and content.
 *
 * @param string $file Filename being processed.
 * @param string $content The content of the file.
 * @return string The content with all input filters applied.
 */
	public function input($file, $content) {
	    $ext = $this->_settings['ext'];
	    $path = $this->_settings['path'];
	    $options = array('file' => $this->_settings['file']);
	    App::import('Vendor', $path, $options);
	    #foreach ($exts as $extension) {
		if (strtolower(substr($file, -strlen($ext))) == $ext) {
		    $lessc = new lessc();
		    return $lessc->parse($content);
		}
	    #}
	    return $content;
	}
}