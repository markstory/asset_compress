<?php
namespace AssetCompress\Shell\Task;

use App\Console\Command\AppShell;
use AssetCompress\Factory;
use Cake\Console\Shell;
use Cake\Utility\Folder;
use MiniAsset\AssetConfig;
use MiniAsset\AssetTarget;

class AssetBuildTask extends Shell
{

    protected $config;
    protected $factory;

    /**
     * Set the Configuration object that will be used.
     *
     * @param \AssetCompress\AssetConfig $config The config object.
     * @return void
     */
    public function setConfig(AssetConfig $config)
    {
        $this->config = $config;
        $this->factory = new Factory($config);
    }

    /**
     * Build all the files declared in the Configuration object.
     *
     * @return void
     */
    public function build()
    {
        $themes = (array)$this->config->general('themes');
        foreach ($themes as $theme) {
            $this->_io->verbose('Building with theme = ' . $theme);
            $this->config->theme($theme);
            foreach ($this->factory->assetCollection() as $target) {
                if ($target->isThemed()) {
                    $this->_buildTarget($target);
                }
            }
        }
        $this->_io->verbose('Building un-themed targets.');
        foreach ($this->factory->assetCollection() as $target) {
            $this->_buildTarget($target);
        }
    }

    /**
     * Generate and save the cached file for a build target.
     *
     * @param AssetTarget $build The build to generate.
     * @return void
     */
    protected function _buildTarget(AssetTarget $build)
    {
        $writer = $this->factory->writer();
        $compiler = $this->factory->compiler();

        $name = $writer->buildFileName($build);
        if ($writer->isFresh($build) && empty($this->params['force'])) {
            $this->out('<info>Skip building</info> ' . $name . ' existing file is still fresh.');

            return;
        }

        $writer->invalidate($build);
        $name = $writer->buildFileName($build);
        try {
            $this->out('<success>Saving file</success> for ' . $name);
            $contents = $compiler->generate($build);
            $writer->write($build, $contents);
        } catch (Exception $e) {
            $this->err('Error: ' . $e->getMessage());
        }
    }
}
