<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Application extends BaseApplication
{
    const VERSION = '@package_version@';

    public function __construct()
    {
        parent::__construct('PkTool', self::VERSION);
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($this->checkNewerVersion()) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                'A new version of PkTool is available, run `pktool self-update` to update to the latest version.'
            ));
            $output->writeln('');
        }

        $oldWorkingDir = getcwd();
        $newWorkingDir = $this->getNewWorkingDir($input);

        if ($newWorkingDir) {
            chdir($newWorkingDir);
        }

        $result = parent::doRun($input, $output);

        chdir($oldWorkingDir);

        return $result;
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption(new InputOption(
            '--working-dir',
            '-w',
            InputOption::VALUE_REQUIRED,
            'If specified, use the given directory as working directory.'
        ));

        return $definition;
    }

    private function getNewWorkingDir(InputInterface $input)
    {
        $workingDir = $input->getParameterOption(array('--working-dir', '-w'));

        if (false !== $workingDir && !is_dir($workingDir)) {
            throw new \RuntimeException('Invalid working directory specified, ' . $workingDir . ' does not exist.');
        }

        return $workingDir;
    }

    private function checkNewerVersion()
    {
        if (self::VERSION === '@' . 'package_version' . '@') {
            return true;
        }

        $content = humbug_get_contents('https://polderknowledge.github.io/pktool/pktool.phar.version');

        return substr($content, 0, 40) !== self::VERSION;
    }
}
