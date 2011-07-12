<?php
App::import('Lib', 'AssetCompress.AssetFilterInterface');
#App::import('Lib', 'AssetCompress.ImportInline', array('file' => 'filter' . DS . 'import_inline.php'));
/**
 * Pre-processing filter that adds support for LESS.css files.
 *
 * @see http://leafo.net/lessphp/
 */
class LessPhp extends AssetFilter {
    
    /**
    * Supported extensions for this processor.
    *
    * @var array
    */
    protected $_extensions = array('.less', '.less.css');

/**
 * Apply all the input filters in sequence to the file and content.
 *
 * @param string $file Filename being processed.
 * @param string $content The content of the file.
 * @return string The content with all input filters applied.
 */
	public function input($file, $content) {
	    $path = 'lessphp/lessc';
	    $options = array('file' => 'lessphp/lessc.inc.php');
	    App::import('Vendor', $path, $options);
	    foreach ($this->_extensions as $extension) {
		if (strtolower(substr($file, -strlen($extension))) == $extension) {
		    $lessc = new lessc();
		    return $lessc->parse($content);
		}
	    }
	    return $content;
	}
}