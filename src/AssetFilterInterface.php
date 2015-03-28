<?php
namespace AssetCompress;

/**
 * AssetFilterInterface all filters declared in your config.ini must implement
 * this interface or exceptions will be thrown.
 */
interface AssetFilterInterface
{

    /**
     * Input filters are used to do pre-processing on each file in a
     * build target.
     *
     * @param string $filename Name of the file
     * @param string $content Content of the file.
     */
    public function input($filename, $content);

    /**
     * Output filters are used to do minification or do other manipulation
     * on the content before $targetFile is saved/output.
     *
     * @param string $target The build target being made.
     * @param string $content The content to filter.
     */
    public function output($targetFile, $content);

    /**
     * Gets settings for this filter. Will always include 'paths'
     * key which points at paths available for the type of asset being generated.
     *
     * @param array $settings Array of settings.
     * @return array Updated Settings.
     */
    public function settings(array $settings = null);

    /**
     * Find any additional filter based dependencies.
     *
     * Preprocessor filters can use this hook method to find a list of dependent
     * files. For example, `import` statements in Less/Sass.
     *
     * @param AssetTarget $target The target to find dependencies for this filter.
     * @return array An array of AssetCompress\File\Local objects.
     */
    public function getDependencies(AssetTarget $target);
}
