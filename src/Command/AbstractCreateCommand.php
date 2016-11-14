<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Command;

use PolderKnowledge\PkTool\Question\Container;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractCreateCommand extends AbstractCommand
{
    /**
     * @var array
     */
    protected $licenses;

    protected function configure()
    {
        parent::configure();

        $this->addOption(
            'source',
            null,
            InputOption::VALUE_OPTIONAL,
            'Define the source repository',
            $this->getDefaultRepository()
        );

        $this->licenses = [
            'proprietary' => __DIR__ . '/../../resources/licenses/proprietary.txt',
            'MIT' => __DIR__ . '/../../resources/licenses/mit.txt',
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->cloneRepository($input, $output);

        $this->replaceVariables($input, $output);

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    protected function cloneRepository(InputInterface $input, OutputInterface $output)
    {
        $targetDirectory = getcwd();

        $source = $input->getOption('source');

        $output->writeln(sprintf('<info>Cloning %s</info>', $source));

        $process = new Process(sprintf('git clone %s %s', $source, $targetDirectory));
        $process->run(function ($type, $buffer) use ($output) {
            $output->writeln($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function replaceVariables(InputInterface $input, OutputInterface $output)
    {
        $container = new Container($this->getHelper('question'));
        $this->populateQuestionContainer($container);

        $values = $container->ask($input, $output);
        $values = $this->populateVariableArray($values);

        $output->writeln('');
        $output->writeln('<info>Replacing variables in cloned repository...</info>');
        $output->writeln('');

        $this->updateLicenseFile('LICENSE.md', $values['license']);

        $finder = $this->getFileFinder();
        $this->replaceVariablesInFinder($finder, $values);
    }

    private function replaceVariablesInFinder(Finder $finder, array $values)
    {
        $finder = $this->getFileFinder();

        foreach ($finder as $file) {
            $this->replaceFile($values, $file->getRealPath());
        }
    }

    private function replaceFile(array $variables, $path)
    {
        $contents = file_get_contents($path);

        $contents = $this->replaceFileContent($path, $contents, $variables);

        file_put_contents($path, $contents);
    }

    protected function replaceFileContent($path, $content, array $variables)
    {
        foreach ($variables as $variable => $value) {
            $content = str_replace('{' . strtoupper($variable) . '}', $value, $content);
        }

        return $content;
    }

    private function updateLicenseFile($path, $license)
    {
        if (!is_file($path)) {
            return;
        }

        if (!array_key_exists($license, $this->licenses)) {
            throw new RuntimeException(sprintf('License "%s" not found', $license));
        }

        $contents = file_get_contents($this->licenses[$license]);

        file_put_contents($path, $contents);
    }

    /**
     * Returns the default repository to clone for cases where the source option has not been provided.
     *
     * @return string
     */
    abstract protected function getDefaultRepository();

    /**
     * Populates the question container with question to ask the user.
     *
     * @param Container $container
     */
    abstract protected function populateQuestionContainer(Container $container);

    /**
     * Populates the variable array with static variable values.
     *
     * @param array $values The array with variables to expand.
     * @return array
     */
    protected function populateVariableArray(array $values)
    {
        $values['year'] = date('Y');

        return $values;
    }

    /**
     * Creates a new instance of a Finder which is used to find the files to replace variables in.
     *
     * @return Finder
     */
    protected function getFileFinder()
    {
        $finder = new Finder();
        $finder->files()->ignoreDotFiles(false)->in(getcwd());

        return $finder;
    }
}
