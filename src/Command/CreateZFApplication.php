<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class CreateZFApplication extends AbstractCommand
{
    const REPOSITORY_URL = 'https://github.com/thephpleague/skeleton';

    protected function configure()
    {
        $this->setName('create-zf-application');
        $this->setDescription('Creates a new Zend Framework application.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>Cloning skeleton-application from %s</info>', self::REPOSITORY_URL));

        $process = new Process(sprintf('git clone %s .', self::REPOSITORY_URL));
        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });

        if (!$process->isSuccessful()) {
            return 1;
        }
    }
}
