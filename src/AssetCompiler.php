<?php
namespace AssetCompress;

use AssetCompress\Filter\FilterRegistry;
use Cake\Core\Configure;
use RuntimeException;

/**
 * Compiles a set of assets together, and applies filters.
 * Forms the center of AssetCompress
 */
class AssetCompiler
{
    /**
     * The filter registry to use.
     *
     * @var AssetCompress\FilterRegistry
     */
    protected $filterRegistry;

    /**
     * Constructor.
     *
     * @param FilterRegistry $filters The filter registry
     * @return void
     */
    public function __construct(FilterRegistry $filters)
    {
        $this->filterRegistry = $filters;
    }

    /**
     * Generate a compiled asset, with all the configured filters applied.
     *
     * @param AssetTarget $target The target to build
     * @return The processed result of $target and it dependencies.
     * @throws RuntimeException
     */
    public function generate(AssetTarget $build)
    {
        $filters = $this->filterRegistry->collection($build);
        $output = '';
        foreach ($build->files() as $file) {
            $content = $file->contents();
            $content = $filters->input($file->path(), $content);
            $output .= $content . "\n";
        }
        if (!Configure::read('debug') || php_sapi_name() === 'cli') {
            $output = $filters->output($build->path(), $output);
        }
        return trim($output);
    }

}
