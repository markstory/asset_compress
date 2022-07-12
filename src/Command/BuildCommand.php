<?php
declare(strict_types=1);

namespace AssetCompress\Command;

use AssetCompress\Config\ConfigFinder;
use AssetCompress\Factory;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;
use MiniAsset\AssetTarget;

/**
 * Command to build target files.
 */
class BuildCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Generate files defined in AssetCompress configuration.')
            ->addOption('force', [
                'help' => 'Force assets to rebuild. Ignores timestamp rules.',
                'short' => 'f',
                'boolean' => true,
            ])
            ->addOption('config', [
                'help' => 'The config file to use.',
                'short' => 'c',
                'default' => CONFIG . 'asset_compress.ini',
            ])
            ->addOption('skip-plugins', [
                'help' => 'Don\'t load config files from plugin\'s .',
                'boolean' => true,
            ]);

        return $parser;
    }

    /**
     * Clear built files.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int The exit code
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $configFinder = new ConfigFinder();
        $config = $configFinder->loadAll(
            $args->getOption('config'),
            (bool)$args->getOption('skip-plugins')
        );
        $factory = new Factory($config);

        $themes = (array)$config->general('themes');
        foreach ($themes as $theme) {
            $io->verbose('Building with theme = ' . $theme);
            $config->theme($theme);
            foreach ($factory->assetCollection() as $target) {
                if ($target->isThemed()) {
                    $this->buildTarget($target, $factory, $args, $io);
                }
            }
        }
        $io->verbose('Building un-themed targets.');
        foreach ($factory->assetCollection() as $target) {
            $this->buildTarget($target, $factory, $args, $io);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Generate and save the cached file for a build target.
     *
     * @param \MiniAsset\AssetTarget $build The build to generate.
     * @param \AssetCompress\Factory $factory Assetcompress factory
     * @param \Cake\Console\Arguments $args Arguments instance
     * @param \Cake\Console\ConsoleIo $io ConsoleIo instance
     * @return void
     */
    protected function buildTarget(AssetTarget $build, Factory $factory, Arguments $args, ConsoleIo $io): void
    {
        $writer = $factory->writer();
        $compiler = $factory->compiler();

        $name = $writer->buildFileName($build);
        if ($writer->isFresh($build) && $args->getOption('force')) {
            $io->out('<info>Skip building</info> ' . $name . ' existing file is still fresh.');

            return;
        }

        $writer->invalidate($build);
        $name = $writer->buildFileName($build);
        try {
            $io->out('<success>Saving file</success> for ' . $name);
            $contents = $compiler->generate($build);
            $writer->write($build, $contents);
        } catch (Exception $e) {
            $io->err('Error: ' . $e->getMessage());
        }
    }
}
